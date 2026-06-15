/**
 * Queue Audio Announcement System
 * Uses Web Speech API (free, built-in browser feature)
 * 
 * Features:
 * - Text-to-Speech announcements
 * - Multiple language support
 * - Volume control
 * - Voice selection
 * - Announcement history
 */

// Prevent redeclaration if script is loaded multiple times
if (typeof QueueAudioService === 'undefined') {
    class QueueAudioService {
    constructor() {
        this.synthesis = window.speechSynthesis;
        this.voices = [];
        this.currentVoice = null;
        this.volume = 1.0; // 0.0 to 1.0
        this.rate = 0.9; // Slightly slower for clarity
        this.pitch = 1.0; // Normal pitch for clarity
        this.lang = 'en-US'; // American English (better voice selection)
        this.enabled = true;
        this.announcementQueue = [];
        this.isAnnouncing = false;
        
        // Load voices when available
        this.loadVoices();
        
        // Chrome loads voices asynchronously
        if (speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = () => this.loadVoices();
        }
        
        // Load settings from localStorage
        this.loadSettings();
    }
    
    /**
     * Load available voices using unified voice configuration
     */
    loadVoices() {
        try {
            this.voices = this.synthesis.getVoices();
            
            // Use unified voice configuration if available
            if (window.voiceConfig && window.voiceConfig.currentVoice) {
                this.currentVoice = window.voiceConfig.currentVoice;
                console.log('Using unified voice configuration:', this.currentVoice?.name);
            } else {
                // Fallback to original logic
                this.currentVoice = this.voices.find(voice => 
                    voice.lang.startsWith('en') && 
                    (voice.name.includes('Google') || voice.name.includes('Microsoft') || voice.name.includes('Alex') || voice.name.includes('Samantha'))
                ) || this.voices.find(voice => 
                    voice.lang.startsWith('en') && 
                    (voice.name.includes('Female') || voice.name.includes('Woman'))
                ) || this.voices.find(voice => 
                    voice.lang.startsWith('en') && 
                    (voice.name.includes('Male') || voice.name.includes('Man'))
                ) || this.voices.find(voice => 
                    voice.lang.startsWith('en') && voice.default
                ) || this.voices.find(voice => 
                    voice.lang.startsWith('en')
                ) || this.voices[0];
                
                console.log('Using fallback voice selection:', this.currentVoice?.name);
            }
            
            console.log('Loaded voices:', this.voices.length);
        } catch (error) {
            console.error('Error loading voices:', error);
            this.voices = [];
            this.currentVoice = null;
        }
    }
    
    /**
     * Load settings from localStorage
     */
    loadSettings() {
        const settings = localStorage.getItem('queueAudioSettings');
        if (settings) {
            try {
                const parsed = JSON.parse(settings);
                this.enabled = parsed.enabled !== false;
                this.volume = parsed.volume || 1.0;
                this.rate = parsed.rate || 0.9;
                this.pitch = parsed.pitch || 1.0;
                this.lang = parsed.lang || 'en-GB';
            } catch (e) {
                console.error('Error loading audio settings:', e);
            }
        }
    }
    
    /**
     * Save settings to localStorage
     */
    saveSettings() {
        const settings = {
            enabled: this.enabled,
            volume: this.volume,
            rate: this.rate,
            pitch: this.pitch,
            lang: this.lang
        };
        localStorage.setItem('queueAudioSettings', JSON.stringify(settings));
    }
    
    /**
     * Announce patient call
     * @param {string} ticketNumber - Queue ticket number
     * @param {string} patientName - Patient name (optional)
     * @param {string} queueType - Queue type (OPD, Lab, etc.)
     * @param {string} location - Where to go (e.g., "Room 3", "Counter 2")
     */
    announcePatient(ticketNumber, patientName = null, queueType = null, location = null) {
        if (!this.enabled) {
            console.log('Audio announcements disabled');
            return;
        }
        
        // Build announcement text with LOUD, CLEAR language
        let announcement = '';
        
        // Strong attention grabber
        announcement += 'ATTENTION! ATTENTION! ';
        
        // Ticket number announcement - LOUD
        if (ticketNumber) {
            announcement += `QUEUE NUMBER ${this.formatTicketNumber(ticketNumber)}. `;
        }
        
        // Patient name announcement - LOUD
        if (patientName) {
            announcement += `PATIENT ${patientName.toUpperCase()}. `;
        }
        
        // Queue type and location - LOUD
        if (queueType && location) {
            announcement += `PLEASE PROCEED TO ${queueType.toUpperCase()} AT ${location.toUpperCase()}. `;
        } else if (queueType) {
            announcement += `PLEASE PROCEED TO ${queueType.toUpperCase()}. `;
        } else if (location) {
            announcement += `PLEASE PROCEED TO ${location.toUpperCase()}. `;
        }
        
        // Pause for clarity
        announcement += '... ';
        
        // Repeat for clarity with simpler language - LOUD
        if (ticketNumber) {
            announcement += `NUMBER ${this.formatTicketNumber(ticketNumber)}. `;
        }
        if (patientName) {
            announcement += `${patientName.toUpperCase()}. `;
        }
        if (location) {
            announcement += `COME TO ${location.toUpperCase()}. `;
        }
        
        announcement += 'THANK YOU.';
        
        // Add to queue and process
        this.queueAnnouncement(announcement);
    }
    
    /**
     * Format ticket number for clear pronunciation
     * "OPD1-20251001-045" → "O P D 1, number 045"
     */
    formatTicketNumber(ticketNumber) {
        const parts = ticketNumber.split('-');
        if (parts.length === 3) {
            // Extract prefix, date, and sequence
            const prefix = parts[0].split('').join(' '); // "OPD1" → "O P D 1"
            const sequence = parts[2]; // "045"
            return `${prefix}, number ${sequence}`;
        }
        // If format doesn't match, spell it out
        return ticketNumber.split('').join(' ');
    }
    
    /**
     * Queue announcement for sequential processing
     */
    queueAnnouncement(text) {
        this.announcementQueue.push(text);
        if (!this.isAnnouncing) {
            this.processQueue();
        }
    }
    
    /**
     * Process announcement queue
     */
    async processQueue() {
        if (this.announcementQueue.length === 0) {
            this.isAnnouncing = false;
            return;
        }
        
        this.isAnnouncing = true;
        const text = this.announcementQueue.shift();
        
        await this.speak(text);
        
        // Small delay between announcements
        setTimeout(() => {
            this.processQueue();
        }, 500);
    }
    
    /**
     * Speak text using Web Speech API with maximum volume and clarity
     */
    speak(text) {
        return new Promise((resolve, reject) => {
            if (!this.synthesis) {
                console.error('Speech synthesis not supported');
                reject(new Error('Speech synthesis not supported'));
                return;
            }
            
            try {
                // Cancel any ongoing speech
                this.synthesis.cancel();
                
                const utterance = new SpeechSynthesisUtterance(text);
                
                // Use unified voice configuration for consistent voice across system
                if (window.voiceConfig && window.voiceConfig.currentVoice) {
                    const voiceConfig = window.voiceConfig.getVoiceConfig();
                    utterance.voice = voiceConfig.voice;
                    utterance.volume = Math.max(1.0, voiceConfig.volume); // Maximum volume
                    utterance.rate = voiceConfig.rate; // Use configured rate
                    utterance.pitch = voiceConfig.pitch; // Use configured pitch
                    utterance.lang = this.lang;
                } else {
                    // Fallback to original configuration
                    utterance.voice = this.currentVoice;
                    utterance.volume = 1.0; // Maximum volume
                    utterance.rate = 0.7; // Slower for better clarity and loudness
                    utterance.pitch = 1.0; // Normal pitch
                    utterance.lang = this.lang;
                }
                
                // Add volume boost by repeating the text
                const boostedText = text + '. ' + text; // Repeat for emphasis
                utterance.text = boostedText;
                
                // Event handlers
                utterance.onend = () => {
                    console.log('Announcement completed');
                    resolve();
                };
                
                utterance.onerror = (error) => {
                    console.error('Speech error:', error);
                    reject(new Error(`Speech error: ${error.error}`));
                };
                
                // Speak
                console.log('Announcing:', text);
                this.synthesis.speak(utterance);
                
                // Timeout fallback
                setTimeout(() => {
                    if (this.synthesis.speaking) {
                        this.synthesis.cancel();
                        reject(new Error('Speech timeout'));
                    }
                }, 30000); // 30 second timeout
                
            } catch (error) {
                console.error('Error creating speech utterance:', error);
                reject(new Error(`Speech creation error: ${error.message}`));
            }
        });
    }
    
    /**
     * Test announcement with volume check
     */
    test() {
        console.log('Testing audio with maximum volume settings...');
        console.log('Voice:', this.currentVoice?.name);
        console.log('Volume:', 1.0);
        console.log('Rate:', 0.7);
        console.log('Pitch:', 1.0);
        this.announcePatient('045', 'John Doe', 'OPD Queue', 'Counter 3');
    }
    
    /**
     * Test volume levels
     */
    testVolume() {
        const testText = 'ATTENTION! This is a volume test. Can you hear me clearly?';
        this.speak(testText);
    }
    
    /**
     * Stop all announcements
     */
    stop() {
        this.synthesis.cancel();
        this.announcementQueue = [];
        this.isAnnouncing = false;
    }
    
    /**
     * Enable announcements
     */
    enable() {
        this.enabled = true;
        this.saveSettings();
    }
    
    /**
     * Disable announcements
     */
    disable() {
        this.enabled = false;
        this.stop();
        this.saveSettings();
    }
    
    /**
     * Set volume (0.0 to 1.0)
     */
    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume));
        this.saveSettings();
    }
    
    /**
     * Set rate (0.1 to 10, default 1)
     */
    setRate(rate) {
        this.rate = Math.max(0.1, Math.min(10, rate));
        this.saveSettings();
    }
    
    /**
     * Set pitch (0 to 2, default 1)
     */
    setPitch(pitch) {
        this.pitch = Math.max(0, Math.min(2, pitch));
        this.saveSettings();
    }
    
    /**
     * Set voice by name
     */
    setVoice(voiceName) {
        const voice = this.voices.find(v => v.name === voiceName);
        if (voice) {
            this.currentVoice = voice;
            this.lang = voice.lang;
            this.saveSettings();
        }
    }
    
    /**
     * Get available voices
     */
    getVoices() {
        return this.voices.map(voice => ({
            name: voice.name,
            lang: voice.lang,
            default: voice.default,
            localService: voice.localService
        }));
    }
    
    /**
     * Play realistic chime before announcement
     */
    playChime() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const now = audioContext.currentTime;
            const duration = 0.8;
            
            // Create a more musical chime with harmonics
            const chimeNotes = [
                { freq: 523.25, gain: 0.4, delay: 0 },    // C5
                { freq: 659.25, gain: 0.3, delay: 0.1 },  // E5
                { freq: 783.99, gain: 0.2, delay: 0.2 }   // G5
            ];
            
            chimeNotes.forEach(({ freq, gain, delay }) => {
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                const filter = audioContext.createBiquadFilter();
                
                oscillator.connect(filter);
                filter.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = freq;
                oscillator.type = 'sine';
                
                // Add harmonics for richer sound
                const harmonic = audioContext.createOscillator();
                const harmonicGain = audioContext.createGain();
                harmonic.frequency.value = freq * 2;
                harmonic.type = 'sine';
                harmonic.connect(harmonicGain);
                harmonicGain.connect(filter);
                harmonicGain.gain.value = 0.3;
                
                // Warm filter
                filter.type = 'lowpass';
                filter.frequency.value = 2000;
                filter.Q.value = 1;
                
                // Natural envelope - MUCH LOUDER
                const envelope = gain * 1.2; // Increase chime volume by 20%
                gainNode.gain.setValueAtTime(0, now + delay);
                gainNode.gain.linearRampToValueAtTime(envelope, now + delay + 0.05);
                gainNode.gain.exponentialRampToValueAtTime(envelope * 0.4, now + delay + duration * 0.6);
                gainNode.gain.exponentialRampToValueAtTime(0.001, now + delay + duration);
                
                oscillator.start(now + delay);
                oscillator.stop(now + delay + duration);
                harmonic.start(now + delay);
                harmonic.stop(now + delay + duration);
            });
        } catch (error) {
            console.error('Error playing chime:', error);
        }
    }
} // End of class QueueAudioService

// Export class to global scope
window.QueueAudioService = QueueAudioService;

// Create global instance with error handling (only if not already created)
if (!window.queueAudio) {
    try {
        window.queueAudio = new QueueAudioService();
        console.log('Queue Audio Service initialized successfully');
    } catch (error) {
        console.error('Failed to initialize Queue Audio Service:', error);
        // Create a fallback object
        window.queueAudio = {
            enabled: false,
            announcePatient: () => console.log('Audio service not available'),
            test: () => console.log('Audio service not available'),
            enable: () => console.log('Audio service not available'),
            disable: () => console.log('Audio service not available'),
            playChime: () => console.log('Audio service not available')
        };
    }
} else {
    console.log('Queue Audio Service already initialized, skipping re-initialization');
}

} else {
    console.log('QueueAudioService already defined, skipping re-initialization');
    // Ensure queueAudio instance exists even if class was already defined
    if (!window.queueAudio) {
        try {
            window.queueAudio = new window.QueueAudioService();
        } catch (error) {
            console.error('Failed to initialize Queue Audio Service:', error);
            window.queueAudio = {
                enabled: false,
                announcePatient: () => console.log('Audio service not available'),
                test: () => console.log('Audio service not available'),
                enable: () => console.log('Audio service not available'),
                disable: () => console.log('Audio service not available'),
                playChime: () => console.log('Audio service not available')
            };
        }
    }
}

// Convenience function for calling patient
window.callPatient = function(ticketNumber, patientName = null, queueType = null, location = null) {
    if (!window.queueAudio || !window.queueAudio.enabled) {
        console.log('Audio service not available or disabled');
        return;
    }
    
    try {
        // Play chime first
        window.queueAudio.playChime();
        
        // Then announce after short delay
        setTimeout(() => {
            window.queueAudio.announcePatient(ticketNumber, patientName, queueType, location);
        }, 600);
    } catch (error) {
        console.error('Error calling patient:', error);
    }
};

console.log('Queue Audio Service loaded. Use window.queueAudio or window.callPatient()');

