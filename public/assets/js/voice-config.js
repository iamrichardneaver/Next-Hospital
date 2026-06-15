/**
 * Unified Voice Configuration System
 * Provides consistent voice selection across all audio services
 */

class VoiceConfig {
    constructor() {
        this.voices = [];
        this.currentVoice = null;
        this.preference = 'male'; // 'male', 'female', 'african-female', 'ghanaian-female'
        this.loadVoices();
    }

    /**
     * Load available voices and select the best one
     */
    loadVoices() {
        if (!('speechSynthesis' in window)) {
            console.log('Speech synthesis not supported');
            return;
        }

        this.voices = speechSynthesis.getVoices();
        
        // Chrome loads voices asynchronously
        if (speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = () => {
                this.voices = speechSynthesis.getVoices();
                this.selectBestVoice();
            };
        }
        
        this.selectBestVoice();
    }

    /**
     * Select the best voice based on preference
     */
    selectBestVoice() {
        if (!this.voices || this.voices.length === 0) {
            console.log('No voices available');
            return;
        }

        const preference = this.getVoicePreference();
        
        switch (preference) {
            case 'male':
                this.currentVoice = this.findMaleVoice();
                break;
            case 'female':
                this.currentVoice = this.findFemaleVoice();
                break;
            case 'african-female':
                this.currentVoice = this.findAfricanFemaleVoice();
                break;
            case 'ghanaian-female':
                this.currentVoice = this.findGhanaianFemaleVoice();
                break;
            default:
                this.currentVoice = this.findMaleVoice(); // Default to male (clearer)
        }

        console.log('Selected voice:', this.currentVoice?.name, 'for preference:', preference);
    }

    /**
     * Get voice preference from localStorage or default
     */
    getVoicePreference() {
        const saved = localStorage.getItem('voicePreference');
        return saved || 'male'; // Default to male (clearer voice)
    }

    /**
     * Set voice preference
     */
    setVoicePreference(preference) {
        this.preference = preference;
        localStorage.setItem('voicePreference', preference);
        this.selectBestVoice();
    }

    /**
     * Find the clearest male voice
     */
    findMaleVoice() {
        const maleVoices = [
            'Microsoft David Desktop', // Very clear male voice
            'Google US English Male',
            'Alex', // macOS male voice
            'Daniel', // macOS male voice
            'Microsoft Mark Desktop',
            'Microsoft Richard Desktop'
        ];

        // Try to find preferred male voices first
        for (const preferred of maleVoices) {
            const voice = this.voices.find(v => 
                v.name.includes(preferred) && v.lang.startsWith('en')
            );
            if (voice) return voice;
        }

        // Fallback: find any English male voice
        return this.voices.find(voice => 
            voice.lang.startsWith('en') && 
            (voice.name.toLowerCase().includes('male') || 
             voice.name.toLowerCase().includes('man') ||
             voice.name.toLowerCase().includes('david') ||
             voice.name.toLowerCase().includes('alex') ||
             voice.name.toLowerCase().includes('daniel'))
        ) || this.voices.find(voice => voice.lang.startsWith('en')) || this.voices[0];
    }

    /**
     * Find the clearest female voice
     */
    findFemaleVoice() {
        const femaleVoices = [
            'Microsoft Zira Desktop', // Clear female voice
            'Google US English Female',
            'Samantha', // macOS female voice
            'Victoria', // macOS female voice
            'Microsoft Susan Desktop',
            'Microsoft Hazel Desktop'
        ];

        // Try to find preferred female voices first
        for (const preferred of femaleVoices) {
            const voice = this.voices.find(v => 
                v.name.includes(preferred) && v.lang.startsWith('en')
            );
            if (voice) return voice;
        }

        // Fallback: find any English female voice
        return this.voices.find(voice => 
            voice.lang.startsWith('en') && 
            (voice.name.toLowerCase().includes('female') || 
             voice.name.toLowerCase().includes('woman') ||
             voice.name.toLowerCase().includes('zira') ||
             voice.name.toLowerCase().includes('samantha') ||
             voice.name.toLowerCase().includes('victoria'))
        ) || this.voices.find(voice => voice.lang.startsWith('en')) || this.voices[0];
    }

    /**
     * Find African female voice (if available)
     * Optimized for clarity, speed, and authority
     */
    findAfricanFemaleVoice() {
        // Prioritize clearest, most authoritative female voices
        const authoritativeVoices = [
            'Microsoft Zira Desktop', // Very clear and authoritative
            'Google US English Female', // Clear and fast
            'Microsoft Susan Desktop', // Authoritative tone
            'Microsoft Hazel Desktop', // Clear pronunciation
            'Samantha', // macOS voice - clear and professional
            'Victoria', // macOS voice - good clarity
            'Karen', // macOS voice - authoritative
            'Tessa' // macOS voice - clear South African accent if available
        ];

        // Try to find African-specific voices first (rare but preferred)
        let voice = this.voices.find(v => 
            v.lang.includes('en-ZA') || // South African English
            v.lang.includes('en-NG') || // Nigerian English
            v.lang.includes('en-KE') || // Kenyan English
            v.name.toLowerCase().includes('african') ||
            v.name.toLowerCase().includes('south african') ||
            v.name.toLowerCase().includes('tessa') // South African voice on macOS
        );

        if (voice) return voice;

        // Try authoritative voices in order of preference
        for (const preferred of authoritativeVoices) {
            voice = this.voices.find(v => 
                v.name.includes(preferred) && v.lang.startsWith('en')
            );
            if (voice) return voice;
        }

        // Fallback to any clear English female voice
        voice = this.voices.find(voice => 
            voice.lang.startsWith('en') && 
            (voice.name.toLowerCase().includes('female') || 
             voice.name.toLowerCase().includes('woman') ||
             voice.name.toLowerCase().includes('zira') ||
             voice.name.toLowerCase().includes('susan') ||
             voice.name.toLowerCase().includes('hazel'))
        );

        return voice || this.voices.find(voice => voice.lang.startsWith('en')) || this.voices[0];
    }

    /**
     * Find Ghanaian female voice (optimized for Ghanaian English pronunciation)
     * Prioritizes voices that work well with Ghanaian English patterns
     */
    findGhanaianFemaleVoice() {
        // Voices that work well with Ghanaian English pronunciation patterns
        const ghanaianOptimizedVoices = [
            'Microsoft Zira Desktop', // Excellent clarity and warmth
            'Google US English Female', // Clear pronunciation, good for Ghanaian English
            'Microsoft Susan Desktop', // Authoritative and clear
            'Microsoft Hazel Desktop', // Clear pronunciation
            'Samantha', // macOS voice - warm and clear
            'Victoria', // macOS voice - good for Ghanaian English patterns
            'Karen', // macOS voice - authoritative
            'Tessa', // South African voice - closest to West African English
            'Moira', // Irish voice - similar rhythm to Ghanaian English
            'Fiona', // Scottish voice - clear pronunciation
            'Veena', // Indian English voice - good for Commonwealth English
            'Raveena' // Indian English voice - clear and warm
        ];

        // Try to find Ghanaian-specific voices first (rare but preferred)
        let voice = this.voices.find(v => 
            v.lang.includes('en-GH') || // Ghanaian English (if available)
            v.lang.includes('en-NG') || // Nigerian English (similar to Ghanaian)
            v.name.toLowerCase().includes('ghana') ||
            v.name.toLowerCase().includes('west african') ||
            v.name.toLowerCase().includes('tessa') || // South African voice
            v.name.toLowerCase().includes('moira') || // Irish voice
            v.name.toLowerCase().includes('veena') || // Indian English
            v.name.toLowerCase().includes('raveena') // Indian English
        );

        if (voice) return voice;

        // Try Ghanaian-optimized voices in order of preference
        for (const preferred of ghanaianOptimizedVoices) {
            voice = this.voices.find(v => 
                v.name.includes(preferred) && v.lang.startsWith('en')
            );
            if (voice) return voice;
        }

        // Fallback to any clear English female voice with Commonwealth characteristics
        voice = this.voices.find(voice => 
            voice.lang.startsWith('en') && 
            (voice.name.toLowerCase().includes('female') || 
             voice.name.toLowerCase().includes('woman') ||
             voice.name.toLowerCase().includes('zira') ||
             voice.name.toLowerCase().includes('susan') ||
             voice.name.toLowerCase().includes('hazel') ||
             voice.name.toLowerCase().includes('samantha') ||
             voice.name.toLowerCase().includes('victoria'))
        );

        return voice || this.voices.find(voice => voice.lang.startsWith('en')) || this.voices[0];
    }

    /**
     * Get voice configuration for speech synthesis
     */
    getVoiceConfig() {
        const preference = this.getVoicePreference();
        
        const configs = {
            'male': {
                voice: this.currentVoice,
                rate: 0.7, // Slower for clarity
                pitch: 1.0, // Normal pitch
                volume: 1.0 // Maximum volume
            },
            'female': {
                voice: this.currentVoice,
                rate: 0.8, // Slightly faster
                pitch: 1.1, // Slightly higher pitch
                volume: 1.0
            },
            'african-female': {
                voice: this.currentVoice,
                rate: 0.9, // Faster and clearer
                pitch: 1.1, // Slightly higher for authority
                volume: 1.0
            },
            'ghanaian-female': {
                voice: this.currentVoice,
                rate: 0.95, // Very clear and fast for Ghanaian English
                pitch: 1.15, // Higher pitch for clarity and warmth
                volume: 1.0 // Maximum volume for loudness
            }
        };

        return configs[preference] || configs['male'];
    }

    /**
     * Get all available voices for selection UI
     */
    getAvailableVoices() {
        return this.voices.filter(voice => voice.lang.startsWith('en'));
    }

    /**
     * Test voice with given text
     */
    testVoice(text = 'Good morning! This is a test of the voice system. Please listen carefully. Thank you very much!') {
        if (!this.currentVoice) {
            console.error('No voice selected');
            return;
        }

        const config = this.getVoiceConfig();
        
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.voice = config.voice;
        utterance.rate = config.rate;
        utterance.pitch = config.pitch;
        utterance.volume = config.volume;
        utterance.lang = 'en-US';

        speechSynthesis.cancel();
        speechSynthesis.speak(utterance);
    }
}

// Create global instance
window.voiceConfig = new VoiceConfig();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VoiceConfig;
}
