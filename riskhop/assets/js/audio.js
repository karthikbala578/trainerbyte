/**
 * RiskHOP Audio Manager
 * Handles all game sound effects
 */

const AudioManager = {
  // Audio file paths
  sounds: {
    dice: null,
    snake: null,
    ladder: null,
    wildcard: null,
    bonus: null,
    audit: null,
    warning: null,
    win: null,
    loss: null,
    dice_decrease: null,
    dice_increase: null,
    cell_decrease: null,
    cell_increase: null,
  },

  // Audio settings
  settings: {
    enabled: true,
    volume: 0.7,
    basePath: "../assets/audio/",
  },

  /**
   * Initialize audio system
   */
  init: function () {
    // Preload all audio files
    this.sounds.dice = this.loadAudio("dice.mp3");
    this.sounds.snake = this.loadAudio("snake.mp3");
    this.sounds.ladder = this.loadAudio("ladder.mp3");
    this.sounds.wildcard = this.loadAudio("wildcard.mp3");
    this.sounds.bonus = this.loadAudio("bonus.mp3");
    this.sounds.audit = this.loadAudio("audit.mp3");
    this.sounds.warning = this.loadAudio("warning.mp3");
    this.sounds.win = this.loadAudio("win.mp3");
    this.sounds.loss = this.loadAudio("loss.mp3");

    // Wildcard specific sounds
    this.sounds.dice_decrease = this.loadAudio("dice_decrease.mp3");
    this.sounds.dice_increase = this.loadAudio("dice_increase.mp3");
    this.sounds.cell_decrease = this.loadAudio("cell_decrease.mp3");
    this.sounds.cell_increase = this.loadAudio("cell_increase.mp3");

    // Set initial volume for all sounds
    Object.keys(this.sounds).forEach((key) => {
      if (this.sounds[key]) {
        this.sounds[key].volume = this.settings.volume;
      }
    });

    // console.log('🔊 Audio Manager initialized');
  },

  /**
   * Load audio file
   */
  loadAudio: function (filename) {
    try {
      const audio = new Audio(this.settings.basePath + filename);
      audio.preload = "auto";
      return audio;
    } catch (error) {
      console.warn(`Failed to load audio: ${filename}`, error);
      return null;
    }
  },

  /**
   * Play sound
   */
  play: function (soundName) {
    if (!this.settings.enabled) {
      return;
    }

    const audio = this.sounds[soundName];
    if (audio) {
      // Reset to beginning if already playing
      audio.currentTime = 0;

      // Play with error handling
      const playPromise = audio.play();
      if (playPromise !== undefined) {
        playPromise.catch((error) => {
          console.warn(`Failed to play ${soundName}:`, error);
        });
      }
    } else {
      console.warn(`Sound not found: ${soundName}`);
    }
  },

  /**
   * Stop sound
   */
  stop: function (soundName) {
    const audio = this.sounds[soundName];
    if (audio) {
      audio.pause();
      audio.currentTime = 0;
    }
  },

  /**
   * Stop all sounds
   */
  stopAll: function () {
    Object.keys(this.sounds).forEach((key) => {
      if (this.sounds[key]) {
        this.sounds[key].pause();
        this.sounds[key].currentTime = 0;
      }
    });
  },

  /**
   * Set volume (0.0 to 1.0)
   */
  setVolume: function (volume) {
    this.settings.volume = Math.max(0, Math.min(1, volume));

    Object.keys(this.sounds).forEach((key) => {
      if (this.sounds[key]) {
        this.sounds[key].volume = this.settings.volume;
      }
    });
  },

  /**
   * Enable audio
   */
  enable: function () {
    this.settings.enabled = true;
    console.log("🔊 Audio enabled");
  },

  /**
   * Disable audio
   */
  disable: function () {
    this.settings.enabled = false;
    this.stopAll();
    console.log("🔇 Audio disabled");
  },

  /**
   * Toggle audio on/off
   */
  toggle: function () {
    if (this.settings.enabled) {
      this.disable();
    } else {
      this.enable();
    }
    return this.settings.enabled;
  },

  // ==========================================
  // GAME-SPECIFIC AUDIO FUNCTIONS
  // ==========================================

  /**
   * Play dice roll sound
   */
  playDiceRoll: function () {
    this.play("dice");
  },

  /**
   * Play snake sound (based on protection percentage)
   * @param {number} protectionPercent - 0 to 100
   */
  playSnake: function (protectionPercent) {
    this.play("snake");
  },

  /**
   * Play ladder sound (based on exploitation percentage)
   * @param {number} exploitationPercent - 0 to 100
   */
  playLadder: function (exploitationPercent) {
    this.play("ladder");
  },

  /**
   * Play wildcard reveal sound
   */
  playWildcard: function () {
    this.play("wildcard");
  },

  /**
   * Play bonus cell sound
   */
  playBonus: function () {
    this.play("bonus");
  },

  /**
   * Play audit cell sound
   */
  playAudit: function () {
    this.play("audit");
  },

  /**
   * Play win sound
   */
  playWin: function () {
    this.play("win");
  },

  /**
   * Play warning sound
   */
  playWarning: function () {
    this.play("warning");
  },

  /**
   * Play loss sound
   */
  playLoss: function () {
    this.play("loss");
  },
  /**
   * Play dice decrease sound
   */
  playDiceDecrease: function () {
    this.play("dice_decrease");
  },

  /**
   * Play dice increase sound
   */
  playDiceIncrease: function () {
    this.play("dice_increase");
  },

  /**
   * Play cell decrease sound
   */
  playCellDecrease: function () {
    this.play("cell_decrease");
  },

  /**
   * Play cell increase sound
   */
  playCellIncrease: function () {
    this.play("cell_increase");
  },
};

// Initialize audio on page load
document.addEventListener("DOMContentLoaded", function () {
  AudioManager.init();

  // FIX: Unlock audio context on first user interaction (click/tap)
  function unlockAudio() {
    // We only need to play one dummy sound briefly to unlock the whole context
    // This avoids the issue where pausing all sounds interrupts the first roll.
    const dummy = AudioManager.sounds.warning;
    if (dummy) {
      const playPromise = dummy.play();
      if (playPromise !== undefined) {
        playPromise.then(() => {
          dummy.pause();
          dummy.currentTime = 0;
        }).catch(() => { });
      }
    }

    document.removeEventListener('click', unlockAudio);
    document.removeEventListener('touchstart', unlockAudio);
  }

  document.addEventListener('click', unlockAudio);
  document.addEventListener('touchstart', unlockAudio);
});

// Export for use in other files
window.AudioManager = AudioManager;
