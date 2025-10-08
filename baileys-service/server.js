const express = require('express');
const cors = require('cors');
const makeWASocket = require('@whiskeysockets/baileys').default;
const { useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion } = require('@whiskeysockets/baileys');
const P = require('pino');
const fs = require('fs');
const path = require('path');
const axios = require('axios');

const app = express();
app.use(cors());
app.use(express.json());

const PORT = process.env.PORT || 3001;
const AUTH_DIR = path.join(__dirname, 'auth_sessions');
const WEBHOOK_URL = process.env.WEBHOOK_URL || null;

// Store active sessions
const sessions = new Map();
const qrCodes = new Map();
const sessionWebhooks = new Map();
const messageJobs = new Map(); // Track async message sending jobs

// Create auth directory if it doesn't exist
if (!fs.existsSync(AUTH_DIR)) {
    fs.mkdirSync(AUTH_DIR, { recursive: true });
}

// Logger configuration
const logger = P({ level: 'silent' });

/**
 * Create or get WhatsApp session
 */
async function createSession(sessionId) {
    if (sessions.has(sessionId)) {
        return { success: true, message: 'Session already exists' };
    }

    try {
        const sessionPath = path.join(AUTH_DIR, sessionId);
        if (!fs.existsSync(sessionPath)) {
            fs.mkdirSync(sessionPath, { recursive: true });
        }

        const { state, saveCreds } = await useMultiFileAuthState(sessionPath);
        const { version } = await fetchLatestBaileysVersion();

        const sock = makeWASocket({
            version,
            auth: state,
            logger,
            printQRInTerminal: false,
            browser: ['OvoWpp', 'Chrome', '1.0.0'],
        });

        // Handle credentials update
        sock.ev.on('creds.update', saveCreds);

        // Handle connection updates
        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                qrCodes.set(sessionId, qr);
                console.log(`QR Code generated for session: ${sessionId}`);
            }

            if (connection === 'close') {
                const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;
                
                if (shouldReconnect) {
                    console.log(`Reconnecting session: ${sessionId}`);
                    sessions.delete(sessionId);
                    setTimeout(() => createSession(sessionId), 3000);
                } else {
                    console.log(`Session logged out: ${sessionId}`);
                    sessions.delete(sessionId);
                    qrCodes.delete(sessionId);
                }
            } else if (connection === 'open') {
                console.log(`Session connected: ${sessionId}`);
                qrCodes.delete(sessionId);
            }
        });

        // Handle incoming messages
        sock.ev.on('messages.upsert', async ({ messages, type }) => {
            if (type !== 'notify') return;

            for (const msg of messages) {
                if (!msg.message || msg.key.fromMe) continue;

                const webhookUrl = sessionWebhooks.get(sessionId);
                if (!webhookUrl) continue;

                try {
                    // Get profile picture URL
                    let profilePicUrl = null;
                    try {
                        const jid = msg.key.remoteJid;
                        profilePicUrl = await sock.profilePictureUrl(jid, 'image');
                    } catch (err) {
                        // Profile picture not available
                        console.log(`No profile picture for ${msg.key.remoteJid}`);
                    }

                    const messageData = {
                        sessionId,
                        messageId: msg.key.id,
                        from: msg.key.remoteJid.replace('@s.whatsapp.net', ''),
                        timestamp: msg.messageTimestamp,
                        message: msg.message.conversation || 
                                msg.message.extendedTextMessage?.text || 
                                '',
                        messageType: 'text',
                        pushName: msg.pushName || '',
                        profilePicUrl: profilePicUrl
                    };

                    // Handle media messages
                    if (msg.message.imageMessage) {
                        messageData.messageType = 'image';
                        messageData.caption = msg.message.imageMessage.caption || '';
                        messageData.mimetype = msg.message.imageMessage.mimetype;
                    } else if (msg.message.documentMessage) {
                        messageData.messageType = 'document';
                        messageData.caption = msg.message.documentMessage.caption || '';
                        messageData.fileName = msg.message.documentMessage.fileName;
                        messageData.mimetype = msg.message.documentMessage.mimetype;
                    } else if (msg.message.videoMessage) {
                        messageData.messageType = 'video';
                        messageData.caption = msg.message.videoMessage.caption || '';
                        messageData.mimetype = msg.message.videoMessage.mimetype;
                    }

                    await axios.post(webhookUrl, messageData).catch(err => {
                        console.error(`Webhook error for session ${sessionId}:`, err.message);
                    });

                } catch (error) {
                    console.error(`Error processing message for session ${sessionId}:`, error);
                }
            }
        });

        // Handle message status updates (delivered, read, failed)
        sock.ev.on('messages.update', async (updates) => {
            const webhookUrl = sessionWebhooks.get(sessionId);
            if (!webhookUrl) return;

            for (const update of updates) {
                try {
                    const statusData = {
                        type: 'status_update',
                        sessionId,
                        messageId: update.key.id,
                        status: null
                    };

                    // Map Baileys status to system status
                    // 1=send, 2=delivered, 3=read, 9=failed
                    if (update.update.status === 3) {
                        statusData.status = 2; // delivered
                    } else if (update.update.status === 4) {
                        statusData.status = 3; // read
                    } else if (update.update.status === 5) {
                        statusData.status = 9; // failed
                    }

                    // Only send webhook if status is mapped
                    if (statusData.status) {
                        await axios.post(webhookUrl, statusData).catch(err => {
                            console.error(`Status webhook error for session ${sessionId}:`, err.message);
                        });
                    }

                } catch (error) {
                    console.error(`Error processing status update for session ${sessionId}:`, error);
                }
            }
        });

        sessions.set(sessionId, sock);
        return { success: true, message: 'Session created successfully' };

    } catch (error) {
        console.error(`Error creating session ${sessionId}:`, error);
        return { success: false, message: error.message };
    }
}

/**
 * Get session status
 */
function getSessionStatus(sessionId) {
    const sock = sessions.get(sessionId);
    const qr = qrCodes.get(sessionId);
    
    if (!sock) {
        return { connected: false, hasQR: false };
    }

    return {
        connected: sock.user ? true : false,
        hasQR: qr ? true : false,
        user: sock.user || null
    };
}

/**
 * Delete session
 */
async function deleteSession(sessionId) {
    const sock = sessions.get(sessionId);
    
    if (sock) {
        await sock.logout();
        sessions.delete(sessionId);
    }
    
    qrCodes.delete(sessionId);
    
    // Remove auth files
    const sessionPath = path.join(AUTH_DIR, sessionId);
    if (fs.existsSync(sessionPath)) {
        fs.rmSync(sessionPath, { recursive: true, force: true });
    }
    
    return { success: true, message: 'Session deleted' };
}

/**
 * Send message
 */
async function sendMessage(sessionId, to, message, options = {}) {
    try {
        const sock = sessions.get(sessionId);
        
        if (!sock || !sock.user) {
            throw new Error('Session not connected');
        }

        // Ensure proper JID format
        let jid = to;
        if (!to.includes('@')) {
            jid = `${to}@s.whatsapp.net`;
        }

        let content = { text: message };
        
        // Handle media
        if (options.mediaType && options.mediaUrl) {
            console.log(`Sending ${options.mediaType} from URL: ${options.mediaUrl}`);
            
            switch(options.mediaType) {
                case 'image':
                    content = {
                        image: { url: options.mediaUrl },
                        caption: message || options.caption || ''
                    };
                    break;
                case 'document':
                    content = {
                        document: { url: options.mediaUrl },
                        mimetype: options.mimeType || 'application/pdf',
                        fileName: options.fileName || 'document.pdf',
                        caption: message || options.caption || ''
                    };
                    break;
                case 'video':
                    content = {
                        video: { url: options.mediaUrl },
                        caption: message || options.caption || ''
                    };
                    break;
            }
        }

        console.log(`Sending message to ${jid}...`);
        const result = await sock.sendMessage(jid, content);
        console.log(`Message sent successfully: ${result.key.id}`);
        
        return { 
            success: true, 
            message: 'Message sent',
            messageId: result.key.id
        };
    } catch (error) {
        console.error(`Error sending message: ${error.message}`);
        throw error;
    }
}

// API Routes

/**
 * POST /session/start - Start a new session
 */
app.post('/session/start', async (req, res) => {
    const { sessionId } = req.body;
    
    if (!sessionId) {
        return res.status(400).json({ error: 'sessionId is required' });
    }

    const result = await createSession(sessionId);
    res.json(result);
});

/**
 * GET /session/qr/:sessionId - Get QR code for session
 */
app.get('/session/qr/:sessionId', (req, res) => {
    const { sessionId } = req.params;
    const qr = qrCodes.get(sessionId);
    
    if (!qr) {
        return res.status(404).json({ error: 'QR code not available' });
    }
    
    res.json({ qr });
});

/**
 * GET /session/status/:sessionId - Get session status
 */
app.get('/session/status/:sessionId', (req, res) => {
    const { sessionId } = req.params;
    const status = getSessionStatus(sessionId);
    res.json(status);
});

/**
 * DELETE /session/:sessionId - Delete session
 */
app.delete('/session/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const result = await deleteSession(sessionId);
    res.json(result);
});

/**
 * POST /message/send - Send message (ASYNC with webhook callback)
 */
app.post('/message/send', async (req, res) => {
    const { sessionId, to, message, mediaType, mediaUrl, mimeType, fileName, caption, callbackUrl } = req.body;
    
    if (!sessionId || !to) {
        return res.status(400).json({ error: 'sessionId and to are required' });
    }

    if (!message && !mediaType) {
        return res.status(400).json({ error: 'Either message or mediaType is required' });
    }

    // Generate unique job ID
    const jobId = `job_${Date.now()}_${Math.random().toString(36).substring(7)}`;
    
    // Store job status
    messageJobs.set(jobId, {
        status: 'processing',
        sessionId,
        to,
        createdAt: new Date().toISOString()
    });

    // Respond immediately with 202 Accepted
    res.status(202).json({ 
        success: true,
        jobId,
        status: 'processing',
        message: 'Message is being sent in background'
    });

    // Process message sending in background
    setImmediate(async () => {
        try {
            const options = {
                mediaType,
                mediaUrl,
                mimeType,
                fileName,
                caption
            };
            
            console.log(`[Job ${jobId}] Starting background message send...`);
            const result = await sendMessage(sessionId, to, message, options);
            
            // Update job status
            messageJobs.set(jobId, {
                status: 'completed',
                messageId: result.messageId,
                completedAt: new Date().toISOString()
            });
            
            console.log(`[Job ${jobId}] Message sent successfully: ${result.messageId}`);
            
            // Call webhook if provided
            if (callbackUrl) {
                try {
                    await axios.post(callbackUrl, {
                        jobId,
                        status: 'sent',
                        messageId: result.messageId,
                        sessionId,
                        to
                    });
                    console.log(`[Job ${jobId}] Webhook callback sent to ${callbackUrl}`);
                } catch (webhookError) {
                    console.error(`[Job ${jobId}] Webhook callback failed:`, webhookError.message);
                }
            }
            
        } catch (error) {
            console.error(`[Job ${jobId}] Message send failed:`, error.message);
            
            // Update job status
            messageJobs.set(jobId, {
                status: 'failed',
                error: error.message,
                failedAt: new Date().toISOString()
            });
            
            // Call webhook with failure
            if (callbackUrl) {
                try {
                    await axios.post(callbackUrl, {
                        jobId,
                        status: 'failed',
                        error: error.message,
                        sessionId,
                        to
                    });
                } catch (webhookError) {
                    console.error(`[Job ${jobId}] Webhook failure callback failed:`, webhookError.message);
                }
            }
        }
    });
});

/**
 * POST /webhook/set - Set webhook URL for session
 */
app.post('/webhook/set', (req, res) => {
    const { sessionId, webhookUrl } = req.body;
    
    if (!sessionId || !webhookUrl) {
        return res.status(400).json({ error: 'sessionId and webhookUrl are required' });
    }

    sessionWebhooks.set(sessionId, webhookUrl);
    res.json({ success: true, message: 'Webhook URL set successfully' });
});

/**
 * GET /health - Health check
 */
app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        activeSessions: sessions.size,
        pendingQRs: qrCodes.size
    });
});

// Start server with increased timeout for large file uploads
const server = app.listen(PORT, '127.0.0.1', () => {
    console.log(`Baileys WhatsApp Service running on http://127.0.0.1:${PORT}`);
    console.log(`Active sessions will be stored in: ${AUTH_DIR}`);
});

// Increase server timeout to 5 minutes for large media uploads
server.timeout = 300000; // 5 minutes
server.keepAliveTimeout = 305000; // 5 minutes + 5 seconds

// Graceful shutdown
process.on('SIGINT', async () => {
    console.log('Shutting down gracefully...');
    for (const [sessionId, sock] of sessions) {
        try {
            await sock.end();
        } catch (error) {
            console.error(`Error closing session ${sessionId}:`, error);
        }
    }
    process.exit(0);
});
