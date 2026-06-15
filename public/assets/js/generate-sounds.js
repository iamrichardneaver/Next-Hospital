/**
 * Generate notification sounds using Web Audio API
 * This script creates simple notification sounds and saves them as data URLs
 */

class SoundGenerator {
    constructor() {
        this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }

    /**
     * Generate a simple beep sound
     */
    generateBeep(frequency = 800, duration = 0.5, type = 'sine') {
        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        oscillator.frequency.value = frequency;
        oscillator.type = type;
        
        // Create envelope
        gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.3, this.audioContext.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + duration);
        
        oscillator.start(this.audioContext.currentTime);
        oscillator.stop(this.audioContext.currentTime + duration);
    }

    /**
     * Generate standard notification (pleasant beep)
     */
    generateStandard() {
        this.generateBeep(800, 0.3, 'sine');
    }

    /**
     * Generate urgent notification (double beep)
     */
    generateUrgent() {
        this.generateBeep(1000, 0.2, 'sine');
        setTimeout(() => {
            this.generateBeep(1000, 0.2, 'sine');
        }, 300);
    }

    /**
     * Generate critical notification (triple beep with different frequencies)
     */
    generateCritical() {
        this.generateBeep(1200, 0.15, 'sine');
        setTimeout(() => {
            this.generateBeep(1000, 0.15, 'sine');
        }, 200);
        setTimeout(() => {
            this.generateBeep(800, 0.15, 'sine');
        }, 400);
    }

    /**
     * Test all sounds
     */
    testAll() {
        console.log('Testing standard notification...');
        this.generateStandard();
        
        setTimeout(() => {
            console.log('Testing urgent notification...');
            this.generateUrgent();
        }, 1000);
        
        setTimeout(() => {
            console.log('Testing critical notification...');
            this.generateCritical();
        }, 2000);
    }
}

// Create global instance
window.soundGenerator = new SoundGenerator();

// Test function
window.testNotificationSounds = function() {
    window.soundGenerator.testAll();
};

console.log('Sound Generator loaded. Use window.testNotificationSounds() to test sounds.');
