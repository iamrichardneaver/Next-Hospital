/**
 * Workflow Audio Notification Service
 * 
 * Monitors role-based queues and plays audio notifications when new work arrives.
 * Web application only - does not affect mobile app.
 */

class WorkflowNotificationService {
    constructor() {
        // Wait for app config to be available
        this.waitForAppConfig().then(() => {
            this.baseUrl = window.appConfig.baseUrl;
            this.apiUrl = window.appConfig.route('notification-preferences');
            this.checkInterval = 30000; // Default 30 seconds
            this.intervalId = null;
            this.lastCheckTime = null;
            this.audioContext = null;
            this.preferences = null;
            this.isEnabled = true;
            this.hasPermission = false;
            
            // Initialize audio players
            this.initAudioPlayers();
            
            // Load preferences and start monitoring
            this.init();
        });
    }

    /**
     * Wait for app config to be available
     */
    async waitForAppConfig() {
        return new Promise((resolve) => {
            if (window.appConfig) {
                resolve();
            } else {
                const checkConfig = () => {
                    if (window.appConfig) {
                        resolve();
                    } else {
                        setTimeout(checkConfig, 100);
                    }
                };
                checkConfig();
            }
        });
    }
    
    /**
     * Initialize audio context for sound generation
     * AudioContext must be created after user gesture due to browser security policies
     */
    initAudioPlayers() {
        // Don't create AudioContext immediately - wait for user gesture
        this.audioContext = null;
        console.log('Audio context will be initialized on first user interaction');
    }
    
    /**
     * Initialize audio context on user gesture
     */
    initAudioContextOnUserGesture() {
        if (this.audioContext && this.audioContext.state !== 'closed') {
            return; // Already initialized
        }
        
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Resume context if suspended
            if (this.audioContext.state === 'suspended') {
                this.audioContext.resume();
            }
            
            console.log('Audio context initialized for workflow notifications');
        } catch (error) {
            console.error('Failed to initialize audio context:', error);
            this.audioContext = null;
        }
    }
    
    /**
     * Initialize the service
     */
    async init() {
        try {
            // Check current notification permission status (don't request yet)
            if ('Notification' in window) {
                this.hasPermission = Notification.permission === 'granted';
            }
            
            // Load user preferences
            await this.loadPreferences();
            
            // Start monitoring if enabled
            if (this.preferences && this.preferences.audio_enabled) {
                this.startMonitoring();
            }
            
            console.log('Workflow Notification Service initialized');
        } catch (error) {
            console.error('Failed to initialize notification service:', error);
        }
    }
    
    /**
     * Request browser notification permission (must be called from user interaction)
     */
    async requestNotificationPermission() {
        try {
            if ('Notification' in window) {
                if (Notification.permission === 'default') {
                    const permission = await Notification.requestPermission();
                    this.hasPermission = permission === 'granted';
                    return permission;
                }
                return Notification.permission;
            }
            return 'unsupported';
        } catch (error) {
            console.error('Failed to request notification permission:', error);
            return 'denied';
        }
    }
    
    /**
     * Load user notification preferences
     */
    async loadPreferences() {
        try {
            const response = await fetch(`${this.apiUrl}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                if (response.status === 401) {
                    console.log('User not authenticated - using default notification preferences');
                    this.setDefaultPreferences();
                    return this.preferences;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.preferences = data.data;
                this.checkInterval = (this.preferences.check_interval || 30) * 1000;
                
                // Audio volume is now handled in the sound generation methods
                
                return this.preferences;
            }
        } catch (error) {
            console.error('Failed to load notification preferences:', error);
            this.setDefaultPreferences();
            return this.preferences;
        }
    }

    /**
     * Set default preferences when user is not authenticated
     */
    setDefaultPreferences() {
        this.preferences = {
            audio_enabled: true,
            audio_volume: 80,
            check_interval: 30,
            desktop_notification: false,
            use_speech_synthesis: true,
            enhanced_audio: true,
            notify_opd_queue: true,
            notify_lab_queue: true,
            notify_pharmacy_queue: true,
            notify_emergency_queue: true,
            notify_triage_queue: true,
            notify_routine: true,
            notify_urgent: true,
            notify_critical: true,
            do_not_disturb: false,
            dnd_start: '22:00',
            dnd_end: '06:00'
        };
        this.checkInterval = (this.preferences.check_interval || 30) * 1000;
    }
    
    /**
     * Start monitoring for new work
     */
    startMonitoring() {
        if (this.intervalId) {
            this.stopMonitoring();
        }
        
        // Initial check
        this.lastCheckTime = new Date().toISOString();
        this.checkForNewWork();
        
        // Set up interval
        this.intervalId = setInterval(() => {
            this.checkForNewWork();
        }, this.checkInterval);
        
        console.log(`Started monitoring with ${this.checkInterval / 1000}s interval`);
    }
    
    /**
     * Stop monitoring
     */
    stopMonitoring() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
            console.log('Stopped monitoring');
        }
    }
    
    /**
     * Check for new work items
     */
    async checkForNewWork() {
        if (!this.isEnabled || !this.preferences || !this.preferences.audio_enabled) {
            return;
        }
        
        try {
            // Use appConfig.route() to get the correct full URL for the endpoint
            let url = window.appConfig.route('notification-preferences/check-new-work');
            
            // Add query parameters if lastCheckTime exists
            if (this.lastCheckTime) {
                const urlObj = new URL(url);
                urlObj.searchParams.append('last_check', this.lastCheckTime);
                url = urlObj.toString();
            }
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                if (response.status === 401) {
                    console.log('User not authenticated - stopping notification monitoring');
                    this.stopMonitoring();
                    return;
                }
                console.error(`Check failed: ${response.status}`);
                return;
            }
            
            const data = await response.json();
            
            if (data.success && data.has_new_work) {
                this.handleNewWork(data.notifications);
            }
            
            // Update last check time
            this.lastCheckTime = data.timestamp || new Date().toISOString();
            
        } catch (error) {
            console.error('Failed to check for new work:', error);
        }
    }
    
    /**
     * Handle new work notifications
     */
    handleNewWork(notifications) {
        if (!notifications || notifications.length === 0) {
            return;
        }
        
        // Group notifications by priority to play the most urgent sound
        const priorities = notifications.map(n => n.priority || 'routine');
        let soundToPlay = 'standard';
        
        if (priorities.includes('critical') || priorities.includes('emergency')) {
            soundToPlay = 'critical';
        } else if (priorities.includes('urgent')) {
            soundToPlay = 'urgent';
        }
        
        // Play audio notification
        this.playSound(soundToPlay);
        
        // Show browser desktop notification
        if (this.hasPermission && this.preferences.desktop_notification) {
            this.showDesktopNotification(notifications);
        }
        
        // Show in-app notification
        this.showInAppNotification(notifications);
        
        // Dispatch custom event for other parts of the app
        window.dispatchEvent(new CustomEvent('workflow:new-work', {
            detail: { notifications }
        }));
    }
    
    /**
     * Play notification sound using enhanced human-like audio
     */
    playSound(soundType = 'standard') {
        // Initialize audio context on first use (user gesture)
        this.initAudioContextOnUserGesture();
        
        if (!this.audioContext) {
            console.warn('Audio context not available');
            return;
        }

        try {
            const volume = (this.preferences?.audio_volume || 80) / 100;
            
            // Try speech synthesis first for more human-like sound
            if (this.preferences?.use_speech_synthesis !== false) {
                this.playHumanLikeSpeech(soundType, volume);
            }
            
            // Also play the enhanced audio for better attention
            switch (soundType) {
                case 'standard':
                    this.generateHumanLikeStandardSound(volume);
                    break;
                case 'urgent':
                    this.generateHumanLikeUrgentSound(volume);
                    break;
                case 'critical':
                    this.generateHumanLikeCriticalSound(volume);
                    break;
                default:
                    this.generateHumanLikeStandardSound(volume);
            }
        } catch (error) {
            console.error('Error playing sound:', error);
        }
    }

    /**
     * Play human-like speech using Web Speech API with unified voice configuration
     */
    playHumanLikeSpeech(soundType, volume) {
        if (!('speechSynthesis' in window)) {
            console.log('Speech synthesis not supported');
            return;
        }

        // Cancel any ongoing speech
        speechSynthesis.cancel();

        const messages = {
            'standard': 'New work item available',
            'urgent': 'Urgent work item requires attention',
            'critical': 'Critical alert - immediate action required'
        };

        const message = messages[soundType] || messages['standard'];
        
        const utterance = new SpeechSynthesisUtterance(message);
        
        // Use unified voice configuration
        if (window.voiceConfig && window.voiceConfig.currentVoice) {
            const voiceConfig = window.voiceConfig.getVoiceConfig();
            utterance.voice = voiceConfig.voice;
            utterance.rate = voiceConfig.rate;
            utterance.pitch = voiceConfig.pitch;
            utterance.volume = Math.min(volume, voiceConfig.volume); // Use the smaller of the two volumes
            utterance.lang = 'en-US';
        } else {
            // Fallback to original configuration
            utterance.volume = volume;
            utterance.rate = 0.85;
            utterance.pitch = 1.0;
            utterance.lang = 'en-US';
        }
        
        // Add natural pauses and emphasis
        utterance.text = this.addNaturalSpeechPatterns(message, soundType);
        
        // Play the speech
        speechSynthesis.speak(utterance);
    }

    /**
     * Add natural speech patterns to make it sound more human
     */
    addNaturalSpeechPatterns(message, soundType) {
        const patterns = {
            'standard': `Attention please. ${message}. Please check your queue.`,
            'urgent': `Urgent notice. ${message}. Please respond immediately.`,
            'critical': `Critical alert. ${message}. This requires immediate attention.`
        };
        
        return patterns[soundType] || patterns['standard'];
    }

    /**
     * Generate human-like standard notification sound
     */
    generateHumanLikeStandardSound(volume = 0.8) {
        const duration = 0.8;
        const now = this.audioContext.currentTime;
        
        // Create a more human-like sound using voice-like frequencies
        const voiceFrequencies = [
            { freq: 200, type: 'sine', gain: 0.6 }, // Fundamental frequency (male voice range)
            { freq: 400, type: 'sine', gain: 0.4 }, // First harmonic
            { freq: 600, type: 'sine', gain: 0.3 }, // Second harmonic
            { freq: 800, type: 'sine', gain: 0.2 }, // Third harmonic
            { freq: 1000, type: 'sine', gain: 0.15 } // Fourth harmonic
        ];
        
        voiceFrequencies.forEach(({ freq, type, gain: oscGain }) => {
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            const filter = this.audioContext.createBiquadFilter();
            const compressor = this.audioContext.createDynamicsCompressor();
            
            oscillator.connect(filter);
            filter.connect(compressor);
            compressor.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            
            oscillator.frequency.value = freq;
            oscillator.type = type;
            
            // Add natural vibrato (like human voice)
            const vibrato = this.audioContext.createOscillator();
            const vibratoGain = this.audioContext.createGain();
            vibrato.frequency.value = 4.5; // Natural vibrato rate
            vibratoGain.gain.value = 8; // Subtle vibrato depth
            vibrato.connect(vibratoGain);
            vibratoGain.connect(oscillator.frequency);
            vibrato.start(now);
            vibrato.stop(now + duration);
            
            // Add formant filtering to simulate vocal tract
            filter.type = 'bandpass';
            filter.frequency.value = freq * 1.2;
            filter.Q.value = 2;
            
            // Add compression for more natural dynamics
            compressor.threshold.value = -24;
            compressor.knee.value = 30;
            compressor.ratio.value = 12;
            compressor.attack.value = 0.003;
            compressor.release.value = 0.25;
            
            // Natural envelope with breath-like attack
            const envelope = volume * oscGain * 2.0; // Much louder
            gainNode.gain.setValueAtTime(0, now);
            gainNode.gain.linearRampToValueAtTime(envelope * 0.3, now + 0.02); // Gentle attack
            gainNode.gain.linearRampToValueAtTime(envelope, now + 0.1); // Sustain
            gainNode.gain.exponentialRampToValueAtTime(envelope * 0.6, now + duration * 0.6); // Gradual decay
            gainNode.gain.exponentialRampToValueAtTime(0.001, now + duration); // Natural release
            
            oscillator.start(now);
            oscillator.stop(now + duration);
        });
    }

    /**
     * Generate human-like urgent notification sound
     */
    generateHumanLikeUrgentSound(volume = 0.8) {
        const duration = 0.6;
        const now = this.audioContext.currentTime;
        
        // First call - higher pitch for urgency
        this.generateHumanLikeCall(now, 300, duration, volume * 0.9);
        
        // Second call after natural pause
        setTimeout(() => {
            this.generateHumanLikeCall(this.audioContext.currentTime, 300, duration, volume * 0.9);
        }, 500);
    }

    /**
     * Generate human-like critical notification sound
     */
    generateHumanLikeCriticalSound(volume = 0.8) {
        const now = this.audioContext.currentTime;
        const duration = 0.5;
        
        // Create a more urgent, attention-grabbing pattern
        const pattern = [
            { time: 0, freq: 400, duration: 0.3 },
            { time: 0.4, freq: 350, duration: 0.3 },
            { time: 0.8, freq: 450, duration: 0.3 },
            { time: 1.2, freq: 300, duration: 0.3 }
        ];
        
        pattern.forEach(({ time, freq, duration: noteDuration }) => {
            this.generateHumanLikeCall(now + time, freq, noteDuration, volume * 0.8);
        });
    }

    /**
     * Generate a single human-like call
     */
    generateHumanLikeCall(startTime, frequency, duration, volume) {
        // Create multiple harmonics for rich, voice-like sound
        const harmonics = [
            { freq: frequency, gain: 0.7 },
            { freq: frequency * 2, gain: 0.4 },
            { freq: frequency * 3, gain: 0.25 },
            { freq: frequency * 4, gain: 0.15 },
            { freq: frequency * 0.5, gain: 0.3 } // Subharmonic for warmth
        ];
        
        harmonics.forEach(({ freq, gain: harmonicGain }) => {
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            const filter = this.audioContext.createBiquadFilter();
            const compressor = this.audioContext.createDynamicsCompressor();
            
            oscillator.connect(filter);
            filter.connect(compressor);
            compressor.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            
            oscillator.frequency.value = freq;
            oscillator.type = 'sine';
            
            // Add natural vibrato
            const vibrato = this.audioContext.createOscillator();
            const vibratoGain = this.audioContext.createGain();
            vibrato.frequency.value = 5 + Math.random() * 2; // Slight variation
            vibratoGain.gain.value = 6 + Math.random() * 3;
            vibrato.connect(vibratoGain);
            vibratoGain.connect(oscillator.frequency);
            vibrato.start(startTime);
            vibrato.stop(startTime + duration);
            
            // Voice-like filtering
            filter.type = 'bandpass';
            filter.frequency.value = freq * (1.1 + Math.random() * 0.2);
            filter.Q.value = 1.5;
            
            // Natural compression
            compressor.threshold.value = -20;
            compressor.knee.value = 25;
            compressor.ratio.value = 8;
            compressor.attack.value = 0.002;
            compressor.release.value = 0.2;
            
            // Very loud and clear envelope
            const envelope = volume * harmonicGain * 2.5; // Much louder
            gainNode.gain.setValueAtTime(0, startTime);
            gainNode.gain.linearRampToValueAtTime(envelope * 0.4, startTime + 0.01);
            gainNode.gain.linearRampToValueAtTime(envelope, startTime + 0.05);
            gainNode.gain.exponentialRampToValueAtTime(envelope * 0.7, startTime + duration * 0.5);
            gainNode.gain.exponentialRampToValueAtTime(0.001, startTime + duration);
            
            oscillator.start(startTime);
            oscillator.stop(startTime + duration);
        });
    }

    /**
     * Generate standard notification sound (more realistic)
     */
    generateStandardSound(volume = 0.8) {
        const duration = 0.6;
        const now = this.audioContext.currentTime;
        
        // Create multiple oscillators for richer sound
        const oscillators = [
            { freq: 800, type: 'sine', gain: 0.4 },
            { freq: 1200, type: 'sine', gain: 0.2 },
            { freq: 400, type: 'triangle', gain: 0.3 }
        ];
        
        oscillators.forEach(({ freq, type, gain: oscGain }) => {
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            const filter = this.audioContext.createBiquadFilter();
            
            oscillator.connect(filter);
            filter.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            
            oscillator.frequency.value = freq;
            oscillator.type = type;
            
            // Add subtle vibrato for more natural sound
            const lfo = this.audioContext.createOscillator();
            const lfoGain = this.audioContext.createGain();
            lfo.frequency.value = 5; // 5Hz vibrato
            lfoGain.gain.value = 10; // 10Hz modulation depth
            lfo.connect(lfoGain);
            lfoGain.connect(oscillator.frequency);
            lfo.start(now);
            lfo.stop(now + duration);
            
            // Add low-pass filter for warmer sound
            filter.type = 'lowpass';
            filter.frequency.value = 2000;
            filter.Q.value = 1;
            
            // More natural envelope with attack, sustain, and release - LOUDER
            const envelope = volume * oscGain * 1.5; // Increase overall volume by 50%
            gainNode.gain.setValueAtTime(0, now);
            gainNode.gain.linearRampToValueAtTime(envelope * 0.9, now + 0.05); // Quick attack
            gainNode.gain.linearRampToValueAtTime(envelope, now + 0.1); // Sustain
            gainNode.gain.exponentialRampToValueAtTime(envelope * 0.4, now + duration * 0.7); // Gradual decay
            gainNode.gain.exponentialRampToValueAtTime(0.001, now + duration); // Quick release
            
            oscillator.start(now);
            oscillator.stop(now + duration);
        });
    }

    /**
     * Generate urgent notification sound (more realistic double beep)
     */
    generateUrgentSound(volume = 0.8) {
        const duration = 0.4;
        const now = this.audioContext.currentTime;
        
        // First beep - higher pitch for urgency
        this.generateRealisticBeep(now, 1000, duration, volume * 0.9);
        
        // Second beep after short pause
        setTimeout(() => {
            this.generateRealisticBeep(this.audioContext.currentTime, 1000, duration, volume * 0.9);
        }, 400);
    }
    
    /**
     * Generate a single realistic beep
     */
    generateRealisticBeep(startTime, frequency, duration, volume) {
        // Create harmonic series for more natural sound
        const harmonics = [
            { freq: frequency, gain: 0.5 },
            { freq: frequency * 2, gain: 0.3 },
            { freq: frequency * 3, gain: 0.2 },
            { freq: frequency * 0.5, gain: 0.4 }
        ];
        
        harmonics.forEach(({ freq, gain: harmonicGain }) => {
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            const filter = this.audioContext.createBiquadFilter();
            
            oscillator.connect(filter);
            filter.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            
            oscillator.frequency.value = freq;
            oscillator.type = 'sine';
            
            // Add subtle frequency modulation for natural vibrato
            const lfo = this.audioContext.createOscillator();
            const lfoGain = this.audioContext.createGain();
            lfo.frequency.value = 6 + Math.random() * 2; // Slight random variation
            lfoGain.gain.value = 8 + Math.random() * 4;
            lfo.connect(lfoGain);
            lfoGain.connect(oscillator.frequency);
            lfo.start(startTime);
            lfo.stop(startTime + duration);
            
            // Warm low-pass filter
            filter.type = 'lowpass';
            filter.frequency.value = 1500 + Math.random() * 500;
            filter.Q.value = 0.8;
            
            // Natural envelope - MUCH LOUDER
            const envelope = volume * harmonicGain * 2.0; // Double the volume
            gainNode.gain.setValueAtTime(0, startTime);
            gainNode.gain.linearRampToValueAtTime(envelope * 0.95, startTime + 0.02);
            gainNode.gain.linearRampToValueAtTime(envelope, startTime + 0.05);
            gainNode.gain.exponentialRampToValueAtTime(envelope * 0.5, startTime + duration * 0.6);
            gainNode.gain.exponentialRampToValueAtTime(0.001, startTime + duration);
            
            oscillator.start(startTime);
            oscillator.stop(startTime + duration);
        });
    }

    /**
     * Generate critical notification sound (more realistic alarm pattern)
     */
    generateCriticalSound(volume = 0.8) {
        const now = this.audioContext.currentTime;
        const duration = 0.3;
        
        // Create a more complex critical sound pattern
        const pattern = [
            { time: 0, freq: 1200, duration: 0.2 },
            { time: 0.25, freq: 1000, duration: 0.2 },
            { time: 0.5, freq: 800, duration: 0.2 },
            { time: 0.75, freq: 1200, duration: 0.2 },
            { time: 1.0, freq: 1000, duration: 0.2 },
            { time: 1.25, freq: 800, duration: 0.2 }
        ];
        
        pattern.forEach(({ time, freq, duration: noteDuration }) => {
            this.generateRealisticBeep(now + time, freq, noteDuration, volume * 0.8);
        });
    }
    
    /**
     * Show browser desktop notification
     */
    showDesktopNotification(notifications) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }
        
        const count = notifications.length;
        const title = count === 1 ? 'New Work Item' : `${count} New Work Items`;
        const body = notifications.map(n => n.message).join('\n');
        
        const notification = new Notification(title, {
            body: body,
            icon: `${this.baseUrl}/assets/media/logos/hospital-icon.png`,
            badge: `${this.baseUrl}/assets/media/logos/hospital-icon.png`,
            tag: 'workflow-notification',
            requireInteraction: notifications.some(n => ['critical', 'emergency'].includes(n.priority))
        });
        
        notification.onclick = () => {
            window.focus();
            notification.close();
            
            // Navigate to appropriate page
            const firstNotification = notifications[0];
            if (firstNotification.queue_type) {
                const queueType = firstNotification.queue_type.toLowerCase();
                window.location.href = window.appConfig.route(`queues/${queueType}`);
            }
        };
    }
    
    /**
     * Show in-app notification toast
     */
    showInAppNotification(notifications) {
        const count = notifications.length;
        const message = count === 1 
            ? notifications[0].message 
            : `You have ${count} new work items`;
        
        const patientInfo = notifications[0].patient_name 
            ? `<br><small>${notifications[0].patient_name}</small>`
            : '';
        
        // Create toast notification
        const toastId = 'workflow-notification-toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = 'toast workflow-notification-toast';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        const priority = notifications[0].priority || 'routine';
        const priorityClass = {
            'critical': 'bg-danger',
            'emergency': 'bg-danger',
            'urgent': 'bg-warning',
            'routine': 'bg-primary'
        }[priority] || 'bg-primary';
        
        toast.innerHTML = `
            <div class="toast-header ${priorityClass} text-white">
                <i class="bi bi-bell-fill me-2"></i>
                <strong class="me-auto">New Work Alert</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}${patientInfo}
                <div class="mt-2">
                    <button class="btn btn-sm btn-primary view-queue-btn" data-queue="${notifications[0].queue_type}">
                        View Queue
                    </button>
                </div>
            </div>
        `;
        
        // Add to container
        let container = document.getElementById('workflow-notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'workflow-notification-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        container.appendChild(toast);
        
        // Initialize Bootstrap toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: priority === 'critical' ? false : true,
            delay: priority === 'urgent' ? 10000 : 5000
        });
        bsToast.show();
        
        // Add click handler for view queue button
        toast.querySelector('.view-queue-btn')?.addEventListener('click', () => {
            const queueType = notifications[0].queue_type.toLowerCase();
            window.location.href = window.appConfig.route(`queues/${queueType}`);
        });
        
        // Remove toast after hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
    
    /**
     * Update preferences
     */
    async updatePreferences(newPreferences) {
        try {
            const response = await fetch(`${this.apiUrl}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify(newPreferences)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.preferences = data.data;
                this.checkInterval = (this.preferences.check_interval || 30) * 1000;
                
                // Audio volume is now handled in the sound generation methods
                
                // Restart monitoring with new interval
                if (this.preferences.audio_enabled) {
                    this.startMonitoring();
                } else {
                    this.stopMonitoring();
                }
                
                return this.preferences;
            }
        } catch (error) {
            console.error('Failed to update notification preferences:', error);
            throw error;
        }
    }
    
    /**
     * Enable notifications
     */
    enable() {
        this.isEnabled = true;
        if (this.preferences && this.preferences.audio_enabled) {
            this.startMonitoring();
        }
    }
    
    /**
     * Disable notifications
     */
    disable() {
        this.isEnabled = false;
        this.stopMonitoring();
    }
    
    /**
     * Toggle notifications
     */
    toggle() {
        if (this.isEnabled) {
            this.disable();
        } else {
            this.enable();
        }
        return this.isEnabled;
    }
    
    /**
     * Test notification sound
     */
    testSound(soundType = 'standard') {
        this.playSound(soundType);
        this.showInAppNotification([{
            message: `Testing ${soundType} notification sound`,
            priority: soundType,
            queue_type: 'Test'
        }]);
    }
}

// Initialize global instance when DOM is ready
let workflowNotificationService = null;

document.addEventListener('DOMContentLoaded', () => {
    // Only initialize if user is authenticated
    if (document.querySelector('meta[name="csrf-token"]')) {
        workflowNotificationService = new WorkflowNotificationService();
        
        // Make it globally accessible
        window.workflowNotificationService = workflowNotificationService;
        
        console.log('Workflow notification service is ready');
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WorkflowNotificationService;
}

