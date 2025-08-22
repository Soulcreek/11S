// File: web/src/utils/soundEffects.js
// Description: Simple sound effects for game interactions (using Web Audio API)

class SoundEffects {
  constructor() {
    this.audioContext = null;
    this.enabled = true;
    this.init();
  }

  init() {
    try {
      this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
    } catch (error) {
      console.warn('Audio context not supported:', error);
      this.enabled = false;
    }
  }

  // Generate a beep sound with specified frequency and duration
  beep(frequency = 440, duration = 200, volume = 0.3) {
    if (!this.enabled || !this.audioContext) return;

    try {
      const oscillator = this.audioContext.createOscillator();
      const gainNode = this.audioContext.createGain();

      oscillator.connect(gainNode);
      gainNode.connect(this.audioContext.destination);

      oscillator.frequency.value = frequency;
      oscillator.type = 'sine';

      gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
      gainNode.gain.linearRampToValueAtTime(volume, this.audioContext.currentTime + 0.01);
      gainNode.gain.exponentialRampToValueAtTime(0.001, this.audioContext.currentTime + duration / 1000);

      oscillator.start(this.audioContext.currentTime);
      oscillator.stop(this.audioContext.currentTime + duration / 1000);
    } catch (error) {
      console.warn('Sound generation failed:', error);
    }
  }

  // Specific game sounds
  tick() {
    this.beep(800, 100, 0.1); // High pitch, short duration, low volume
  }

  warning() {
    this.beep(600, 200, 0.2); // Medium pitch, medium duration
  }

  timeout() {
    // Double beep for timeout
    this.beep(400, 300, 0.3);
    setTimeout(() => this.beep(300, 300, 0.3), 150);
  }

  success() {
    // Rising tone for success
    const baseTime = this.audioContext ? this.audioContext.currentTime : 0;
    this.beep(523, 150, 0.2); // C5
    setTimeout(() => this.beep(659, 150, 0.2), 100); // E5
    setTimeout(() => this.beep(784, 200, 0.2), 200); // G5
  }

  goodAnswer() {
    this.beep(660, 200, 0.15); // Pleasant tone
  }

  poorAnswer() {
    this.beep(200, 400, 0.2); // Lower, longer tone
  }

  newQuestion() {
    this.beep(440, 100, 0.1); // Simple notification
  }

  gameStart() {
    // Ascending sequence
    [262, 330, 392].forEach((freq, index) => {
      setTimeout(() => this.beep(freq, 150, 0.15), index * 100);
    });
  }

  toggle() {
    this.enabled = !this.enabled;
    return this.enabled;
  }

  isEnabled() {
    return this.enabled;
  }
}

export default new SoundEffects();
