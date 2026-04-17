/**
 * RiskHOP Game Module JavaScript
 * Game interactions and AJAX handlers
 */

console.log("Game.js Loaded - Version 3");
// alert("GAME JS V3 LOADED"); // Commented out to avoid annoyance but can be enabled if needed


// Global game state
const GameState = {
  sessionId: null,
  matrixId: null,
  currentCell: 1,
  diceRemaining: 0,
  capitalRemaining: 0,
  moveNumber: 0,
  isRolling: false,
  isInvesting: false,
  gameData: null,
  playerInvestments: [],
  currentlyHoveredCell: null,
  openedWildcards: new Set(), // Track opened wildcard cells for revisit rule
};

// Initialize game
document.addEventListener("DOMContentLoaded", function () {
  initializeGame();
  setupEventListeners();
});

/**
 * Initialize game state
 */
function initializeGame() {
  // Check if we're on the play page
  if (document.querySelector(".game-board")) {
    loadGameSession();
  }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
  // Roll dice button
  const rollDiceBtn = document.getElementById("rollDiceBtn");
  if (rollDiceBtn) {
    rollDiceBtn.addEventListener("click", handleDiceRoll);
  }

  // Invest button
  const investBtn = document.getElementById("investBtn");
  if (investBtn) {
    investBtn.addEventListener("click", openInvestmentModal);
  }

  // Continue button
  // const continueBtn = document.getElementById('continueBtn');
  // if (continueBtn) {
  //     continueBtn.addEventListener('click', continueGame);
  // }

  // Pause button
  const pauseBtn = document.getElementById("pauseBtn");
  if (pauseBtn) {
    pauseBtn.addEventListener("click", pauseGame);
  }

  // Exit button
  const exitBtn = document.getElementById("exitBtn");
  if (exitBtn) {
    exitBtn.addEventListener("click", confirmExit);
  }

  // Instruction button
  const instructionBtn = document.getElementById("instructionBtn");
  if (instructionBtn) {
    instructionBtn.addEventListener("click", showInstructions);
  }

  // Cell hover for tooltips
  document.querySelectorAll(".board-cell").forEach((cell) => {
    cell.addEventListener("mouseenter", handleCellHover);
    cell.addEventListener("mouseleave", hideCellTooltip);

    // Touch support for mobile
    cell.addEventListener("touchstart", handleCellHover);
    cell.addEventListener("touchend", hideCellTooltip);
  });
}

/**
 * Load game session
 */
function loadGameSession() {
  // Check if we have server-side initialized data first
  if (window.gameInitData) {
    // Use server-side data if available
    GameState.sessionId = window.gameInitData.sessionId;
    GameState.matrixId = window.gameInitData.matrixId;
    GameState.currentCell = parseInt(window.gameInitData.currentCell) || 1;
    GameState.diceRemaining = parseInt(window.gameInitData.diceRemaining) || 0;
    GameState.capitalRemaining = parseInt(window.gameInitData.capitalRemaining) || 0;
    GameState.gameData = window.gameInitData.gameData;

    // Ensure total_cells is a number
    if (GameState.gameData && GameState.gameData.game && GameState.gameData.game.total_cells) {
      GameState.gameData.game.total_cells = parseInt(GameState.gameData.game.total_cells);
    }

    GameState.playerInvestments = window.gameInitData.playerInvestments;

    updateGameUI();
    initializeButtonState(); // Set initial button state based on loaded cell
    return;
  }

  // Fallback to AJAX call
  fetch("ajax/get_session.php")
    .then((response) => response.json())
    .then((data) => {
      // Null checks for AJAX response
      const res = data.data || data;
      if (data && data.success && res.session) {
        GameState.sessionId = res.session.id || null;
        GameState.matrixId = res.session.matrix_id || null;
        GameState.currentCell = res.session.current_cell
          ? parseInt(res.session.current_cell)
          : 1;
        GameState.diceRemaining = res.session.dice_remaining
          ? parseInt(res.session.dice_remaining)
          : 0;
        GameState.capitalRemaining = res.session.capital_remaining
          ? parseInt(res.session.capital_remaining)
          : 0;
        GameState.gameData = res.game_data || null;

        // Ensure total_cells is a number
        if (GameState.gameData && GameState.gameData.game && GameState.gameData.game.total_cells) {
          GameState.gameData.game.total_cells = parseInt(GameState.gameData.game.total_cells);
        }

        GameState.playerInvestments = res.investments || [];

        updateGameUI();
        initializeButtonState(); // Set initial button state
      } else {
        console.error("Invalid session data received:", data);
        showMessage(
          "No active game session found. Please start a new game.",
          "error",
        );
        // Redirect to game library after a delay
        setTimeout(() => {
          window.location.href = "index.php";
        }, 3000);
      }
    })
    .catch((error) => {
      console.error("Error loading session:", error);
      showMessage(
        "Error loading game session. Please start a new game.",
        "error",
      );
      // Redirect to game library after a delay
      setTimeout(() => {
        window.location.href = "index.php";
      }, 3000);
    });
}

/**
 * Initialize button state based on current cell
 */
function initializeButtonState() {
  console.log("Initializing Button State for Cell:", GameState.currentCell);

  // 1. Always enable on Start (Cell 1)
  if (GameState.currentCell === 1) {
    enableInvestmentButton();
    return;
  }

  // 3. Verify via server (Safe fallback)
  fetch("ajax/get_cell_info.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      matrix_id: GameState.matrixId,
      cell_number: GameState.currentCell,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      const res = data.data || data;
      if (data.success && res.cell_info) {
        const type = res.cell_info.type ? res.cell_info.type.toLowerCase() : "";
        console.log("Current Cell Type:", type);
        if (type === "audit" || type === "bonus" || type === "wildcard") {
          enableInvestmentButton();
        } else {
          disableInvestmentButton();
        }
      } else {
        disableInvestmentButton();
      }
    })
    .catch((err) => {
      console.error("Error checking cell type:", err);
      disableInvestmentButton();
    });
}

/**
 * Update game UI
 */
function updateGameUI(skipToken = false) {
  // Update stats
  const capitalEl = document.getElementById("capitalValue");
  if (capitalEl) capitalEl.textContent = GameState.capitalRemaining;

  const diceEl = document.getElementById("diceValue");
  if (diceEl) diceEl.textContent = GameState.diceRemaining;

  const strategyEl = document.getElementById("strategyCount");
  if (strategyEl) {
    let count = 0;
    if (Array.isArray(GameState.playerInvestments)) {
      count = GameState.playerInvestments.length;
    } else if (GameState.playerInvestments && typeof GameState.playerInvestments === 'object') {
      count = Object.keys(GameState.playerInvestments).length;
    }
    strategyEl.textContent = count;
  }

  // Update position text
  const positionEl = document.getElementById("currentCellValue");
  if (positionEl && GameState.gameData && GameState.gameData.game) {
    positionEl.textContent = `${GameState.currentCell} / ${GameState.gameData.game.total_cells}`;
  }

  // Update player token position
  if (!skipToken) {
    updatePlayerPosition(GameState.currentCell);
  }

  // Update button states
  updateButtonStates();

  // Update arrow activation based on current position
  if (typeof window.updateArrowActivation === 'function') {
    window.updateArrowActivation(GameState.currentCell);
  }
}

/**
 * Update player token position
 */
function updatePlayerPosition(cellNumber, animate = false) {
  console.log("Updating Player Position to:", cellNumber, "Animate:", animate);
  const token = document.querySelector(".player-token");
  const targetCell = document.querySelector(`[data-cell="${cellNumber}"]`);

  if (token && targetCell) {
    if (animate) {
      // Animate step by step
      animateMovement(GameState.currentCell, cellNumber);
    } else {
      // Direct placement - Force Redraw
      token.style.display = "none";
      targetCell.appendChild(token);
      void token.offsetWidth; // Force Reflow
      token.style.display = "flex";

      // Safety Double-Check for Reloads: Do it again after a tick
      setTimeout(() => {
        targetCell.appendChild(token);
        token.style.display = "flex";
      }, 100);
    }
  } else if (!targetCell) {
    console.error("Target Cell not found:", cellNumber);
  }
}

/**
 * Animate player movement step by step
 */
function animateMovement(fromCell, toCell, onComplete = null) {
  const token = document.querySelector(".player-token");
  let currentCell = fromCell;

  // Determine direction
  const direction = fromCell < toCell ? 1 : -1;

  // Sound effect for movement start could go here

  const interval = setInterval(() => {
    // If we've reached the target (or passed it due to fast intervals/logic)
    if ((direction === 1 && currentCell >= toCell) || (direction === -1 && currentCell <= toCell)) {
      clearInterval(interval);
      // Ensure we place token visually at the exact end cell
      updatePlayerPosition(toCell);

      // We don't call checkCellEvent here anymore as it's handled by handleDiceRoll logic
      // except when we want to just run the callback
      if (onComplete) onComplete();
      return;
    }

    currentCell += direction;

    const targetCell = document.querySelector(`[data-cell="${currentCell}"]`);
    if (targetCell && token) {
      // Move token to next cell
      token.style.display = "none";
      targetCell.appendChild(token);

      // Quick flash/reflow
      token.style.display = "flex";

      // Optional: Play step sound here
    }

    // Update position text during animation
    const positionEl = document.getElementById("currentCellValue");
    if (positionEl && GameState.gameData && GameState.gameData.game) {
      positionEl.textContent = `${currentCell} / ${GameState.gameData.game.total_cells}`;
    }
  }, 300); // 300ms per cell
}

/**
 * Handle dice roll
 */
async function handleDiceRoll() {
  if (GameState.isRolling || GameState.diceRemaining <= 0) {
    return;
  }

  GameState.isRolling = true;
  disableInvestmentButton(); // Ensure investment is disabled during roll

  const diceDisplay = document.querySelector(".dice-display");
  const diceImage = document.querySelector(".dice-display img");
  const rollBtn = document.getElementById("rollDiceBtn");

  // Disable button and show rolling animation
  rollBtn.disabled = true;
  diceDisplay.classList.add("rolling");
  // Play dice roll sound
  AudioManager.playDiceRoll();

  // Start visual animation loop immediately
  let animationFrame = 0;
  const rollInterval = setInterval(() => {
    // Cycle through dice images 1-6
    const randomFace = Math.floor(Math.random() * 6) + 1;
    diceImage.src = `../assets/images/dice/${randomFace}.png`;
    animationFrame++;
  }, 100); // Change image every 100ms

  try {
    // Perform fetch in background
    const fetchPromise = fetch("ajax/throw_dice.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        session_id: GameState.sessionId,
      }),
    }).then(res => res.json());

    // Wait for at least 1 second of animation AND the fetch result
    // This ensures the user sees the rolling effect even if the server is instant
    const minAnimationTime = new Promise(resolve => setTimeout(resolve, 1000));

    const [data] = await Promise.all([fetchPromise, minAnimationTime]);

    // Stop animation loop
    clearInterval(rollInterval);
    diceDisplay.classList.remove('rolling');

    if (data.success) {
      const res = data.data || data;

      // Show final result image
      diceImage.src = `../assets/images/dice/${res.dice_value}.png`;

      // Update basic stats but NOT currentCell yet
      GameState.diceRemaining = res.dice_remaining;
      GameState.moveNumber = res.move_number;
      // Update UI stats (except token)
      updateGameUI(true); // true = skipToken update

      const initialTarget = res.to_cell; // Where we landed (base of ladder/snake)
      const finalTarget = res.final_cell; // Where we end up

      // 1. Animate to the dice roll result (Intermediate Step)
      animateMovement(GameState.currentCell, initialTarget, () => {
        // We are now at the intermediate cell
        GameState.currentCell = initialTarget;

        // Show arrow for this cell (snake/ladder path)
        if (typeof window.updateArrowActivation === 'function') {
          window.updateArrowActivation(initialTarget);
        }

        // Check if we have a secondary move (Ladder or Snake) or if we just landed on one
        // outcome_percentage is passed from server now
        const outcome = parseFloat(res.outcome_percentage) || 0;

        if (res.event_type === 'ladder') {
          // LADDER LOGIC — 3 scenarios
          const ladderFrom = initialTarget;
          const ladderTo = (res.cell_info && res.cell_info.data) ? res.cell_info.data.cell_to : finalTarget;

          if (outcome >= 100) {
            // === FULLY INVESTED → CLIMB TO TOP ===
            AudioManager.playLadder(100);
            showEventPopup({
              type: 'ladder_full',
              title: 'Ladder Climbed!',
              subtitle: 'Opportunity Seized!',
              description: `You fully invested in <strong>Cell ${ladderFrom}</strong>. You climb all the way to <strong>Cell ${ladderTo}</strong>!`,
              icon: 'fas fa-star',
              iconClass: 'positive',
              fromCell: ladderFrom,
              toCell: ladderTo,
              percentage: 100,
              btnText: 'Climb Up',
              onContinue: () => {
                if (typeof window.updateArrowActivation === 'function') {
                  window.updateArrowActivation(-1);
                }
                animateMovement(initialTarget, finalTarget, () => {
                  finalizeMove(res, true);
                });
              }
            });

          } else if (outcome > 0) {
            // === PARTIALLY INVESTED → CLIMB PARTIAL ===
            AudioManager.playLadder(outcome);
            showEventPopup({
              type: 'ladder_partial',
              title: 'Partial Climb!',
              subtitle: 'Opportunity Partially Exploited',
              description: `You invested <strong>${outcome.toFixed(1)}%</strong> in <strong>Cell ${ladderFrom}</strong>. You did not invest fully, so you climb to <strong>Cell ${finalTarget}</strong> instead of Cell ${ladderTo}.`,
              icon: 'fas fa-arrow-up',
              iconClass: 'partial',
              fromCell: ladderFrom,
              toCell: finalTarget,
              fullToCell: ladderTo,
              percentage: outcome,
              btnText: 'Continue Climb',
              onContinue: () => {
                if (typeof window.updateArrowActivation === 'function') {
                  window.updateArrowActivation(-1);
                }
                animateMovement(initialTarget, finalTarget, () => {
                  finalizeMove(res, true);
                });
              }
            });

          } else {
            // === NOT INVESTED → CANNOT CLIMB ===
            AudioManager.playWarning();
            showEventPopup({
              type: 'ladder_none',
              title: 'Cannot Climb!',
              subtitle: 'Opportunity Missed',
              description: `You did <strong>not invest</strong> in <strong>Cell ${ladderFrom}</strong>. You cannot climb this ladder and stay at <strong>Cell ${ladderFrom}</strong>.`,
              icon: 'fas fa-times-circle',
              iconClass: 'negative',
              fromCell: ladderFrom,
              toCell: ladderFrom,
              percentage: 0,
              btnText: 'Continue Journey',
              onContinue: () => {
                finalizeMove(res, true);
              }
            });
          }

        } else if (res.event_type === 'snake') {
          // SNAKE LOGIC — 3 scenarios
          const snakeHead = initialTarget;
          const snakeTail = (res.cell_info && res.cell_info.data) ? res.cell_info.data.cell_to : finalTarget;

          if (outcome >= 100) {
            // === FULLY INVESTED → PROTECTED (NO SLIDE) ===
            AudioManager.playLadder(100);
            showEventPopup({
              type: 'snake_full',
              title: 'Snake Protected!',
              subtitle: 'Threat Neutralized!',
              description: `You fully invested in <strong>Cell ${snakeHead}</strong>. You are completely protected and stay at <strong>Cell ${snakeHead}</strong>!`,
              icon: 'fas fa-shield-alt',
              iconClass: 'positive',
              fromCell: snakeHead,
              toCell: snakeHead,
              percentage: 100,
              btnText: 'Continue Journey',
              onContinue: () => {
                if (typeof window.updateArrowActivation === 'function') {
                  window.updateArrowActivation(-1);
                }
                finalizeMove(res, true);
              }
            });

          } else if (outcome > 0) {
            // === PARTIALLY INVESTED → PARTIAL SLIDE ===
            AudioManager.playSnake(outcome);
            showEventPopup({
              type: 'snake_partial',
              title: 'Partial Protection!',
              subtitle: 'Threat Partially Mitigated',
              description: `You invested <strong>${outcome.toFixed(1)}%</strong> in <strong>Cell ${snakeHead}</strong>. You are not fully protected, so you slide to <strong>Cell ${finalTarget}</strong> instead of Cell ${snakeTail}.`,
              icon: 'fas fa-shield-alt',
              iconClass: 'partial',
              fromCell: snakeHead,
              toCell: finalTarget,
              fullToCell: snakeTail,
              percentage: outcome,
              btnText: 'Continue Journey',
              onContinue: () => {
                if (typeof window.updateArrowActivation === 'function') {
                  window.updateArrowActivation(-1);
                }
                animateMovement(initialTarget, finalTarget, () => {
                  finalizeMove(res, true);
                });
              }
            });

          } else {
            // === NOT INVESTED → FULL SLIDE ===
            AudioManager.playSnake(0);
            showEventPopup({
              type: 'snake_none',
              title: 'Snake Bite!',
              subtitle: 'No Protection!',
              description: `You did <strong>not invest</strong> in <strong>Cell ${snakeHead}</strong>. You have no protection and slide all the way down to <strong>Cell ${snakeTail}</strong>!`,
              icon: 'fas fa-skull-crossbones',
              iconClass: 'negative',
              fromCell: snakeHead,
              toCell: snakeTail,
              percentage: 0,
              btnText: 'Continue Journey',
              onContinue: () => {
                if (typeof window.updateArrowActivation === 'function') {
                  window.updateArrowActivation(-1);
                }
                animateMovement(initialTarget, finalTarget, () => {
                  finalizeMove(res, true);
                });
              }
            });
          }
        } else if (initialTarget !== finalTarget) {
          // Catch-all for other movement events
          let handled = false;
          if (res.event_type === 'bonus') {
            AudioManager.playBonus();
            showMessage(res.event_description, "success");
            handled = true;
          }

          setTimeout(() => {
            animateMovement(initialTarget, finalTarget, () => {
              finalizeMove(res, handled);
            });
          }, 1000);
        } else {
          // No secondary move
          let handled = false;
          if (res.event_type === 'bonus') {
            AudioManager.playBonus();
            showMessage(res.event_description, "success");
            handled = true;
          }
          // Note: Audit and Wildcard are NOT handled here, so 'handled' remains false for them
          // allowing checkCellEvent to display their specific Modals/Messages.
          finalizeMove(res, handled);
        }
      });

    } else {
      showMessage(data.message || "Error rolling dice", "error");
      GameState.isRolling = false;
      rollBtn.disabled = false;
      // Reset to a default or keep last
    }

  } catch (error) {
    console.error("Error rolling dice:", error);
    clearInterval(rollInterval);
    showMessage("Error rolling dice", "error");
    GameState.isRolling = false;
    diceDisplay.classList.remove("rolling");
    rollBtn.disabled = false;
  }
}

/**
 * Finalize move state and UI
 */
function finalizeMove(res, suppressFeedback = false) {
  GameState.currentCell = parseInt(res.final_cell);
  GameState.isRolling = false;

  // SYNC WITH SERVER TRUTH
  if (typeof res.capital_remaining !== 'undefined') {
    GameState.capitalRemaining = parseInt(res.capital_remaining, 10);
  }

  // Final UI sync
  updateGameUI(true); // matches server state

  // Check final cell event (for chains or state update)
  checkCellEvent(GameState.currentCell, suppressFeedback);
}

/**
 * Check cell event after landing
 */
function checkCellEvent(cellNumber, suppressFeedback = false) {
  fetch("ajax/get_cell_info.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      matrix_id: GameState.matrixId,
      cell_number: cellNumber,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      const res = data.data || data;
      if (data.success && res.cell_info) {
        handleCellEventType(res.cell_info, suppressFeedback);
      } else {
        // Normal cell (no event) - Disable investment
        disableInvestmentButton();
        updateButtonStates();
      }
    });
}

/**
 * Handle different cell event types
 */
function handleCellEventType(cellInfo, suppressFeedback = false) {
  if (!cellInfo || !cellInfo.type) {
    disableInvestmentButton();
    updateButtonStates();
    return;
  }
  // Standardize type string to lowercase to match server responses
  const type = cellInfo.type.toLowerCase();

  switch (type) {
    case "threat":
      handleThreatEvent(cellInfo, suppressFeedback);
      break;
    case "opportunity":
      handleOpportunityEvent(cellInfo, suppressFeedback);
      break;
    case "bonus":
      handleBonusEvent(cellInfo);
      // Explicitly enable for bonus
      enableInvestmentButton();
      break;
    case "audit":
      handleAuditEvent(cellInfo);
      // Explicitly enable for audit
      enableInvestmentButton();
      break;
    case "wildcard":
      handleWildcardPrompt(cellInfo);
      // Wildcard handling involves a modal, but button can be enabled
      enableInvestmentButton();
      break;
    default:
      // Neutral cell
      disableInvestmentButton();
      updateButtonStates();
  }

  // Extra safety check for button state to ensure it persists
  if (["audit", "bonus", "wildcard"].includes(type)) {
    enableInvestmentButton();
  }
}

/**
 * Handle threat (snake) event — called on page reload / checkCellEvent
 */
function handleThreatEvent(cellInfo, suppressFeedback = false) {
  if (!suppressFeedback) {
    const protection = cellInfo.current_protection !== undefined ? cellInfo.current_protection : calculateProtection(cellInfo.data.id);
    const snakeHead = parseInt(cellInfo.data.cell_from);
    const snakeTail = parseInt(cellInfo.data.cell_to);

    if (protection >= 100) {
      AudioManager.playLadder(100);
      showEventPopup({
        type: 'snake_full',
        title: 'Snake Protected!',
        subtitle: 'Threat Neutralized!',
        description: `You fully invested in <strong>Cell ${snakeHead}</strong>. You are completely protected and stay at <strong>Cell ${snakeHead}</strong>!`,
        icon: 'fas fa-shield-alt',
        iconClass: 'positive',
        fromCell: snakeHead,
        toCell: snakeHead,
        percentage: 100,
        btnText: 'Continue Journey',
        onContinue: null
      });
    } else if (protection > 0) {
      AudioManager.playSnake(protection);
      const partialCell = Math.round(snakeHead - ((snakeHead - snakeTail) * ((100 - protection) / 100)));
      showEventPopup({
        type: 'snake_partial',
        title: 'Partial Protection!',
        subtitle: 'Threat Partially Mitigated',
        description: `You invested <strong>${protection.toFixed(1)}%</strong> in <strong>Cell ${snakeHead}</strong>. You are not fully protected, so you slid to <strong>Cell ${partialCell}</strong> instead of Cell ${snakeTail}.`,
        icon: 'fas fa-shield-alt',
        iconClass: 'partial',
        fromCell: snakeHead,
        toCell: partialCell,
        fullToCell: snakeTail,
        percentage: protection,
        btnText: 'Continue Journey',
        onContinue: null
      });
    } else {
      AudioManager.playSnake(0);
      showEventPopup({
        type: 'snake_none',
        title: 'Snake Bite!',
        subtitle: 'No Protection!',
        description: `You did <strong>not invest</strong> in <strong>Cell ${snakeHead}</strong>. You have no protection and slid all the way down to <strong>Cell ${snakeTail}</strong>!`,
        icon: 'fas fa-skull-crossbones',
        iconClass: 'negative',
        fromCell: snakeHead,
        toCell: snakeTail,
        percentage: 0,
        btnText: 'Continue Journey',
        onContinue: null
      });
    }
  }

  // Disable investment on threat cells
  disableInvestmentButton();
  updateButtonStates();
}

/**
 * Handle opportunity (ladder) event — called on page reload / checkCellEvent
 */
function handleOpportunityEvent(cellInfo, suppressFeedback = false) {
  if (!suppressFeedback) {
    const exploitation = cellInfo.current_exploitation !== undefined ? cellInfo.current_exploitation : calculateExploitation(cellInfo.data.id);
    const ladderFrom = parseInt(cellInfo.data.cell_from);
    const ladderTo = parseInt(cellInfo.data.cell_to);

    if (exploitation >= 100) {
      AudioManager.playLadder(100);
      showEventPopup({
        type: 'ladder_full',
        title: 'Ladder Climbed!',
        subtitle: 'Opportunity Seized!',
        description: `You fully invested in <strong>Cell ${ladderFrom}</strong>. You climbed all the way to <strong>Cell ${ladderTo}</strong>!`,
        icon: 'fas fa-star',
        iconClass: 'positive',
        fromCell: ladderFrom,
        toCell: ladderTo,
        percentage: 100,
        btnText: 'Continue Journey',
        onContinue: null
      });
    } else if (exploitation > 0) {
      AudioManager.playLadder(exploitation);
      const partialCell = Math.round(ladderFrom + ((ladderTo - ladderFrom) * (exploitation / 100)));
      showEventPopup({
        type: 'ladder_partial',
        title: 'Partial Climb!',
        subtitle: 'Opportunity Partially Exploited',
        description: `You invested <strong>${exploitation.toFixed(1)}%</strong> in <strong>Cell ${ladderFrom}</strong>. You did not invest fully, so you climbed to <strong>Cell ${partialCell}</strong> instead of Cell ${ladderTo}.`,
        icon: 'fas fa-arrow-up',
        iconClass: 'partial',
        fromCell: ladderFrom,
        toCell: partialCell,
        fullToCell: ladderTo,
        percentage: exploitation,
        btnText: 'Continue Journey',
        onContinue: null
      });
    } else {
      AudioManager.playWarning();
      showEventPopup({
        type: 'ladder_none',
        title: 'Cannot Climb!',
        subtitle: 'Opportunity Missed',
        description: `You did <strong>not invest</strong> in <strong>Cell ${ladderFrom}</strong>. You cannot climb this ladder and stayed at <strong>Cell ${ladderFrom}</strong>.`,
        icon: 'fas fa-times-circle',
        iconClass: 'negative',
        fromCell: ladderFrom,
        toCell: ladderFrom,
        percentage: 0,
        btnText: 'Continue Journey',
        onContinue: null
      });
    }
  }

  // Disable investment on opportunity cells
  disableInvestmentButton();
  updateButtonStates();
}

/**
 * Handle bonus event
 */
function handleBonusEvent(cellInfo) {
  // Play bonus sound
  AudioManager.playBonus();

  // Ensure bonus amount is a number
  const bonusAmount = parseInt(cellInfo.data.bonus_amount, 10) || 0;

  // Update GameState safely
  GameState.capitalRemaining = parseInt(GameState.capitalRemaining, 10) || 0;
  GameState.capitalRemaining += bonusAmount;

  // showMessage(`Bonus! +${bonusAmount} Risk Capital`, "success"); // Removed simple alert to use animation instead

  // --- TRIGGER ANIMATION ---
  console.log("Triggering Bonus Animation for amount:", bonusAmount);

  const token = document.querySelector(".player-token");
  if (token) {
    const floatEl = document.createElement("div");
    // Add inline styles as a backup in case CSS fails to load or match
    floatEl.className = "bonus-float-anim";
    floatEl.style.position = "absolute";
    floatEl.style.color = "#f39c12";
    floatEl.style.fontSize = "2.5rem";
    floatEl.style.fontWeight = "800";
    floatEl.style.zIndex = "9999";
    floatEl.style.pointerEvents = "none";
    floatEl.style.textShadow = "0 2px 4px rgba(0,0,0,0.5)";

    // Explicitly add animation here to ensure it runs even if CSS class fails
    // We use the keyframes defined in CSS but force the rule here
    floatEl.style.animation = "floatUpFade 2s ease-out forwards";
    floatEl.style.webkitAnimation = "floatUpFade 2s ease-out forwards";

    floatEl.innerHTML = `<i class="fas fa-coins"></i> +${bonusAmount}`;

    // Position relative to the token
    const tokenRect = token.getBoundingClientRect();

    // We append to body to ensure it floats above everything without overflow issues from containers
    document.body.appendChild(floatEl);

    // Calculate absolute position on page
    const left = tokenRect.left + (tokenRect.width / 2) + window.scrollX;
    const top = tokenRect.top + window.scrollY;

    // Centered on the token initially
    floatEl.style.left = left + "px";
    floatEl.style.top = top + "px";
    floatEl.style.transform = "translate(-50%, -50%)"; // Center alignment

    // Remove after animation completes (2s matches CSS)
    setTimeout(() => {
      if (floatEl && floatEl.parentNode) {
        floatEl.remove();
      }
    }, 2000);
  } else {
    console.warn("Player token not found for bonus animation origin.");
  }

  // Update UI and Button State LAST
  updateGameUI();
  enableInvestmentButton();
}

/**
 * Handle audit event
 */
function handleAuditEvent(cellInfo) {
  // Play audit sound
  AudioManager.playAudit();
  showMessage(
    "Audit Cell! You can now review your strategy investments.",
    "info",
  );

  // Enable investment on audit cells
  enableInvestmentButton();
}

/**
 * Handle wildcard prompt
 */
function handleWildcardPrompt(cellInfo) {
  // Enable investment on wildcard cells (when landed)
  enableInvestmentButton();

  // Check if this wildcard was already opened in this session
  // REMOVED at user request: if (GameState.openedWildcards.has(GameState.currentCell)) { ... }
  // Players can now land on the same wildcard cell multiple times and get a prompt.

  // Play wildcard audio immediately when prompt appears
  AudioManager.playWildcard();

  const modal = createWildcardPromptModal();
  document.body.appendChild(modal);
}

/**
 * Create wildcard prompt modal
 */
function createWildcardPromptModal() {
  const modal = document.createElement("div");
  modal.className = "modal-overlay show"; // Added 'show' class
  modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>Wild Card!</h2>
            </div>
            <p style="text-align: center; font-size: 1.1rem; margin: 20px 0;">
                Would you like to draw a wild card?
            </p>
            <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                <button onclick="openWildcardSelection()" class="btn-play-game">
                    Yes, Open Wild Card
                </button>
                <button onclick="skipWildcard()" class="btn-exit-game">
                    No, Skip
                </button>
            </div>
        </div>
    `;
  return modal;
}

/**
 * Open wildcard selection modal
 */
function openWildcardSelection() {
  // Stop wildcard audio when user accepts
  AudioManager.stop('wildcard');

  // Close prompt
  document.querySelector(".modal-overlay").remove();

  // Fetch available wildcards
  fetch("ajax/get_wildcards.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      matrix_id: GameState.matrixId,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const res = data.data || data;
        showWildcardSelectionModal(res.wildcards);
      }
    });
}

/**
 * Show wildcard selection modal
 */
function showWildcardSelectionModal(wildcards) {
  const modal = document.createElement("div");
  modal.className = "modal-overlay show"; // Added 'show' class

  let cardsHTML = "";
  wildcards.forEach((wildcard, index) => {
    const isOpened = wildcard.is_opened;
    const serial = String(index + 1).padStart(4, "0");

    if (isOpened) {
      cardsHTML += `
            <div class="wildcard-card opened" onclick="handleRevealedCardClick(this, '${wildcard.wildcard_name}')">
                <div class="wildcard-icon"><i class="fas fa-eye"></i></div>
                <div class="wildcard-label text-truncate" title="${wildcard.wildcard_name}">${wildcard.wildcard_name.toUpperCase()}</div>
                <div class="wildcard-serial">M/C SERIAL #${serial}</div>
                <div class="opened-badge">REVEALED</div>
            </div>
        `;
    } else {
      cardsHTML += `
            <div class="wildcard-card" onclick="selectWildcard(${wildcard.id})">
                <div class="wildcard-icon">?</div>
                <div class="wildcard-label">UNDISCOVERED</div>
                <div class="wildcard-serial">M/C SERIAL #${serial}</div>
            </div>
        `;
    }
  });

  modal.innerHTML = `
        <div class="modal-content wildcard-selection-modal" style="position: relative; max-width: 550px; width: 95%;">
            <div class="modal-header">
                <h2>Select a Wild Card</h2>
                <button class="btn-close-modal" onclick="skipWildcard()">×</button>
            </div>
            <div class="wildcard-grid">
                ${cardsHTML}
            </div>
            <div id="wildcard-selection-toast" class="wildcard-toast">
                <i class="fas fa-info-circle"></i> This card is already opened. Please choose another!
            </div>
        </div>
    `;

  document.body.appendChild(modal);
}

/**
 * Handle click on an already revealed card
 */
function handleRevealedCardClick(element, name) {
  // Shake animation
  element.classList.remove('card-shake');
  void element.offsetWidth; // Trigger reflow for animation restart
  element.classList.add('card-shake');

  // Show inline toast message
  const toast = document.getElementById('wildcard-selection-toast');
  if (toast) {
    toast.classList.add('show');

    // Auto hide
    if (window._wildcardToastTimeout) clearTimeout(window._wildcardToastTimeout);
    window._wildcardToastTimeout = setTimeout(() => {
      toast.classList.remove('show');
    }, 2500);
  }
}

/**
 * Select wildcard
 */
function selectWildcard(wildcardId) {
  fetch("ajax/open_wildcard.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      session_id: GameState.sessionId,
      wildcard_id: wildcardId,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const res = data.data || data;
        showWildcardResult(res.wildcard, res.effects, res.already_opened);
      }
    });
}

/**
 * Show wildcard result
 */
/**
 * Show wildcard result with specific handling per type
 */
function showWildcardResult(wildcard, effects, alreadyOpened = false) {
  // Mark as opened
  GameState.openedWildcards.add(GameState.currentCell);
  // Close selection modal
  const selectionModal = document.querySelector(".modal-overlay.show");
  if (selectionModal) selectionModal.remove();

  // 1. Analyze Effects to determine active ones
  const activeEffects = [];
  if (effects.cell_change !== 0) activeEffects.push('cell');
  if (effects.dice_change !== 0) activeEffects.push('dice');
  if (effects.capital_change !== 0) activeEffects.push('capital');

  // New logic: Tell the player they have multiple pieces of information first
  function startSequence() {
    if (activeEffects.length >= 2) {
      showAppModal("Wild Card Reveal",
        `<div style="text-align: center; padding: 20px;">
              <div style="font-size: 4rem; color: #3498db; margin-bottom: 20px;"><i class="fas fa-info-circle"></i></div>
              <p style="font-size: 1.4rem; color: #2c3e50;">You have <strong style="color: #3498db; font-size: 1.8rem;">${activeEffects.length}</strong> pieces of information!</p>
              <p style="color: #636e72; margin-top: 10px;">Click below to see each effect step-by-step.</p>
          </div>`,
        "View Information",
        () => processEffect(0)
      );
    } else {
      processEffect(0);
    }
  }

  // Process effects sequentially
  function processEffect(index) {
    if (index >= activeEffects.length) {
      if (activeEffects.length === 0) {
        showMessage("This Wildcard had no effect!", "info");
      }
      updateGameUI();
      // Ensure dice button is enabled after reveal process finishes
      updateButtonStates();
      return;
    }

    const type = activeEffects[index];
    const callback = () => processEffect(index + 1);

    // Audio no longer suppressed based on effect count
    const suppressAudio = alreadyOpened;

    if (type === 'cell') {
      handleWildcardCellMove(effects, wildcard, suppressAudio, alreadyOpened, callback);
    } else if (type === 'dice') {
      handleWildcardDiceChange(effects, wildcard, suppressAudio, alreadyOpened, callback);
    } else if (type === 'capital') {
      handleWildcardCapitalChange(effects, wildcard, suppressAudio, alreadyOpened, callback);
    }
  }

  startSequence();
}

/**
 * Handle Wildcard Cell Movement (Pos/Neg)
 */
function handleWildcardCellMove(effects, wildcard, suppressAudio = false, alreadyOpened = false, onConfirm = null) {
  const change = effects.cell_change;
  const isPositive = change > 0;
  const type = isPositive ? "Movement Boost" : "Step Back";
  const icon = isPositive ? "fas fa-running" : "fas fa-undo-alt";
  const colorClass = isPositive ? "positive" : "negative";

  // Audio (Delayed slightly to ensure it plays after reveal)
  if (!suppressAudio) {
    setTimeout(() => {
      if (isPositive) {
        AudioManager.playCellIncrease();
      } else {
        AudioManager.playCellDecrease();
      }
    }, 200);
  }

  const startCell = GameState.currentCell;
  const endCell = startCell + change;
  const clampedEnd = Math.max(1, Math.min(GameState.gameData.game.total_cells, endCell));

  // Popup Content
  const content = `
    <div class="wildcard-popup-content">
        <div class="popup-icon ${colorClass}"><i class="${icon}"></i></div>
        <div class="popup-change-amount ${colorClass}">${isPositive ? '+' : ''}${change}</div>
        <p class="popup-desc">You have been moved ${Math.abs(change)} positions ${isPositive ? 'forward' : 'backward'}.</p>
        ${alreadyOpened ? '<p style="color: #e67e22; font-weight: bold;">(Already applied - info only)</p>' : ''}
        
        <div class="comparison-container">
            <div class="comp-box">
                <span class="comp-label">Current Cell</span>
                <span class="comp-val">${startCell}</span>
            </div>
            ${!alreadyOpened ? `
            <div class="comp-arrow"><i class="fas fa-arrow-right"></i></div>
            <div class="comp-box">
                <span class="comp-label">New Cell</span>
                <span class="comp-val ${colorClass}">${clampedEnd}</span>
            </div>
            ` : ''}
        </div>
        <p class="popup-instruction">Your position will update after you continue.</p>
    </div>
  `;

  showAppModal("Movement Effect", content, alreadyOpened ? "Close" : "Continue Game", () => {
    if (!alreadyOpened) {
      // Animation step-by-step ONLY after clicking Continue
      GameState.currentCell = clampedEnd;

      animateMovement(startCell, clampedEnd, () => {
        updateGameUI(); // Sync finally
        checkCellEvent(GameState.currentCell);
        if (onConfirm) onConfirm();
      });
    } else {
      if (onConfirm) onConfirm();
    }
  });
}

/**
 * Handle Wildcard Dice Change (Pos/Neg)
 */
function handleWildcardDiceChange(effects, wildcard, suppressAudio = false, alreadyOpened = false, onConfirm = null) {
  const change = effects.dice_change;
  const isPositive = change > 0;
  const icon = "fas fa-dice";
  const colorClass = isPositive ? "positive" : "negative";

  // Audio
  if (!suppressAudio) {
    if (isPositive) {
      AudioManager.playDiceIncrease();
    } else {
      AudioManager.playDiceDecrease();
    }
  }

  const oldVal = GameState.diceRemaining;
  const newVal = alreadyOpened ? oldVal : Math.max(0, oldVal + change);
  if (!alreadyOpened) GameState.diceRemaining = newVal;

  // Popup Content
  const content = `
    <div class="wildcard-popup-content">
        <div class="popup-icon ${colorClass}"><i class="${icon}"></i></div>
        <div class="popup-change-amount ${colorClass}">${isPositive ? '+' : ''}${change}</div>
        <p class="popup-desc">Your remaining dice rolls have been ${isPositive ? 'increased' : 'decreased'}.</p>
        ${alreadyOpened ? '<p style="color: #e67e22; font-weight: bold;">(Already applied - info only)</p>' : ''}
        
        <div class="comparison-container">
            <div class="comp-box">
                <span class="comp-label">Dice</span>
                <span class="comp-val">${oldVal}</span>
            </div>
            ${!alreadyOpened ? `
            <div class="comp-arrow"><i class="fas fa-arrow-right"></i></div>
            <div class="comp-box">
                <span class="comp-label">New Dice</span>
                <span class="comp-val ${colorClass}">${newVal}</span>
            </div>
            ` : ''}
        </div>
    </div>
  `;

  showAppModal("Dice Modifier", content, alreadyOpened ? "Close" : "Continue", () => {
    updateGameUI();
    if (onConfirm) onConfirm();
  });
}

/**
 * Handle Wildcard Capital Change (Pos/Neg)
 */
function handleWildcardCapitalChange(effects, wildcard, suppressAudio = false, alreadyOpened = false, onConfirm = null) {
  const change = effects.capital_change;
  const isPositive = change > 0;
  const icon = isPositive ? "fas fa-coins" : "fas fa-hand-holding-usd";
  const colorClass = isPositive ? "positive" : "negative";

  // Audio
  if (!suppressAudio) {
    if (isPositive) {
      AudioManager.playBonus();
    } else {
      AudioManager.playDiceDecrease();
    }
  }

  const oldVal = GameState.capitalRemaining;
  const newVal = alreadyOpened ? oldVal : Math.max(0, oldVal + change);
  if (!alreadyOpened) GameState.capitalRemaining = newVal;

  // Popup Content
  const content = `
    <div class="wildcard-popup-content">
        <div class="popup-icon ${colorClass}"><i class="${icon}"></i></div>
        <div class="popup-change-amount ${colorClass}">${isPositive ? '+' : ''}${change}</div>
        <p class="popup-desc">Your risk capital has been ${isPositive ? 'boosted' : 'reduced'}.</p>
        ${alreadyOpened ? '<p style="color: #e67e22; font-weight: bold;">(Already applied - info only)</p>' : ''}
        
        <div class="comparison-container">
            <div class="comp-box">
                <span class="comp-label">Capital</span>
                <span class="comp-val">${oldVal}</span>
            </div>
            ${!alreadyOpened ? `
            <div class="comp-arrow"><i class="fas fa-arrow-right"></i></div>
            <div class="comp-box">
                <span class="comp-label">New Capital</span>
                <span class="comp-val ${colorClass}">${newVal}</span>
            </div>
            ` : ''}
        </div>
    </div>
  `;

  if (alreadyOpened) {
    showAppModal(`${wildcard.wildcard_name} (Already Used)`, content, "Close", onConfirm);
    return;
  }

  showAppModal("Capital Change", content, "Continue", () => {
    updateGameUI();
    if (GameState.capitalRemaining > 0) enableInvestmentButton();
  });
}

/**
 * Generic Modal Helper for Wildcards
 */
function showAppModal(title, htmlContent, btnText, onConfirm) {
  const modal = document.createElement("div");
  modal.className = "modal-overlay show";
  modal.innerHTML = `
        <div class="modal-content wildcard-result-modal">
            <div class="modal-header">
                <h2>${title}</h2>
            </div>
            <div class="modal-body">
                ${htmlContent}
            </div>
            <div class="modal-footer">
                <button class="btn-continue-game" id="modalConfirmBtn">
                    ${btnText} <i class="fas fa-chevron-right" style="margin-left: 10px; font-size: 0.8rem;"></i>
                </button>
            </div>
        </div>
    `;

  document.body.appendChild(modal);

  const btn = modal.querySelector("#modalConfirmBtn");
  btn.addEventListener("click", () => {
    modal.remove();
    if (onConfirm) onConfirm();
  });
}

/**
 * Premium Event Popup for Snake & Ladder events
 * Shows investment outcome with proper design matching the threat message format
 * @param {Object} opts - Configuration object
 * @param {string} opts.type - Event type: 'snake_full', 'snake_partial', 'snake_none', 'ladder_full', 'ladder_partial', 'ladder_none'
 * @param {string} opts.title - Main title
 * @param {string} opts.subtitle - Subtitle below icon
 * @param {string} opts.description - Descriptive text (supports HTML)
 * @param {string} opts.icon - FontAwesome icon class
 * @param {string} opts.iconClass - 'positive', 'partial', or 'negative'
 * @param {number} opts.fromCell - Starting cell
 * @param {number} opts.toCell - Destination cell
 * @param {number} [opts.fullToCell] - Full destination (for partial scenarios)
 * @param {number} opts.percentage - Investment percentage (0-100)
 * @param {string} opts.btnText - Button label
 * @param {Function|null} opts.onContinue - Callback on continue click
 */
function showEventPopup(opts) {
  const isSnake = opts.type.startsWith('snake');
  const isLadder = opts.type.startsWith('ladder');

  // Determine colors based on iconClass
  let gradientColors, glowColor, progressColor, badgeText, badgeClass;
  if (opts.iconClass === 'positive') {
    gradientColors = 'linear-gradient(135deg, #2ecc71, #27ae60)';
    glowColor = 'rgba(46, 204, 113, 0.3)';
    progressColor = '#2ecc71';
    badgeText = isSnake ? '🛡️ FULLY PROTECTED' : '⭐ FULLY EXPLOITED';
    badgeClass = 'event-badge-success';
  } else if (opts.iconClass === 'partial') {
    gradientColors = 'linear-gradient(135deg, #f39c12, #e67e22)';
    glowColor = 'rgba(243, 156, 18, 0.3)';
    progressColor = '#f39c12';
    badgeText = isSnake ? '⚠️ PARTIAL PROTECTION' : '⚠️ PARTIAL CLIMB';
    badgeClass = 'event-badge-warning';
  } else {
    gradientColors = 'linear-gradient(135deg, #e74c3c, #c0392b)';
    glowColor = 'rgba(231, 76, 60, 0.3)';
    progressColor = '#e74c3c';
    badgeText = isSnake ? '💀 NO PROTECTION' : '❌ NO INVESTMENT';
    badgeClass = 'event-badge-danger';
  }

  // Build the cell path display (FROM → TO)
  let pathHTML = '';
  if (opts.fromCell !== opts.toCell) {
    const direction = isSnake ? 'down' : 'up';
    const arrowIcon = isSnake ? 'fa-arrow-down' : 'fa-arrow-up';
    pathHTML = `
      <div class="event-path-container">
        <div class="event-path-cell">
          <span class="event-path-label">From</span>
          <span class="event-path-value">Cell ${opts.fromCell}</span>
        </div>
        <div class="event-path-arrow">
          <i class="fas ${arrowIcon}"></i>
        </div>
        <div class="event-path-cell">
          <span class="event-path-label">To</span>
          <span class="event-path-value event-path-highlight" style="color: ${progressColor}">Cell ${opts.toCell}</span>
        </div>
      </div>
    `;
  } else {
    pathHTML = `
      <div class="event-path-container event-path-stay">
        <div class="event-path-cell">
          <span class="event-path-label">Position</span>
          <span class="event-path-value" style="color: ${progressColor}">Cell ${opts.fromCell}</span>
        </div>
        <div class="event-path-badge">
          <i class="fas fa-check-circle" style="color: ${progressColor}"></i> Stay Here
        </div>
      </div>
    `;
  }

  // Build progress bar
  const progressHTML = `
    <div class="event-progress-container">
      <div class="event-progress-header">
        <span class="event-progress-label">${isSnake ? 'Protection' : 'Exploitation'} Level</span>
        <span class="event-progress-percent" style="color: ${progressColor}">${opts.percentage.toFixed(1)}%</span>
      </div>
      <div class="event-progress-bar">
        <div class="event-progress-fill" style="width: ${opts.percentage}%; background: ${gradientColors};"></div>
      </div>
    </div>
  `;

  // Build the modal
  const modal = document.createElement("div");
  modal.className = "modal-overlay show";
  modal.innerHTML = `
    <div class="modal-content event-popup-modal">
      <div class="modal-header">
        <h2>${opts.title}</h2>
      </div>
      <div class="modal-body">
        <div class="event-popup-content">
          <div class="event-popup-icon" style="background: ${gradientColors}; box-shadow: 0 15px 30px ${glowColor};">
            <i class="${opts.icon}"></i>
          </div>
          <div class="event-popup-badge ${badgeClass}">${badgeText}</div>
          <h3 class="event-popup-subtitle">${opts.subtitle}</h3>
          <p class="event-popup-desc">${opts.description}</p>
          ${progressHTML}
          ${pathHTML}
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-event-continue" id="eventPopupContinueBtn" style="background: ${gradientColors}; box-shadow: 0 10px 20px ${glowColor};">
          ${opts.btnText} <i class="fas fa-chevron-right" style="margin-left: 10px; font-size: 0.8rem;"></i>
        </button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  const btn = modal.querySelector("#eventPopupContinueBtn");
  btn.addEventListener("click", () => {
    modal.remove();
    if (opts.onContinue) opts.onContinue();
  });
}

/**
 * Skip wildcard
 */
function skipWildcard() {
  // Stop audio if they skip
  AudioManager.stop('wildcard');

  document.querySelector(".modal-overlay").remove();
  updateButtonStates();
}

/**
 * Open investment modal
 */
function openInvestmentModal() {
  if (GameState.isInvesting) {
    return;
  }

  GameState.isInvesting = true;

  // Fetch all available strategies
  fetch("ajax/get_strategies.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      matrix_id: GameState.matrixId,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const res = data.data || data;
        // Store data for navigation
        GameState.investmentData = res.groups;
        GameState.flatStrategies = res.strategies; // Keep for reference

        // ALWAYS re-sync pending investments from existing playerInvestments when opening
        GameState.pendingInvestments = {};
        const prevInvs = GameState.playerInvestments;
        if (Array.isArray(prevInvs)) {
          prevInvs.forEach(inv => {
            GameState.pendingInvestments[inv.strategy_id] = parseInt(inv.investment_points || inv.points || 0);
          });
        } else if (prevInvs && typeof prevInvs === 'object') {
          Object.values(prevInvs).forEach(inv => {
            GameState.pendingInvestments[inv.strategy_id] = parseInt(inv.investment_points || inv.points || 0);
          });
        }

        showInvestmentMainMenu();
      } else {
        console.error("Failed to load strategies:", data);
        showMessage(data.message || "Failed to load strategies", "error");
        GameState.isInvesting = false;
      }
    })
    .catch(error => {
      console.error("Error fetching strategies:", error);
      showMessage("Network error loading strategies", "error");
      GameState.isInvesting = false;
    });
}

/**
 * Show Investment Main Menu
 */
function showInvestmentMainMenu() {
  closeInvestmentModal(); // Close if open to refresh

  const modal = document.createElement("div");
  modal.className = "modal-overlay show";
  modal.id = "investmentModal";

  modal.innerHTML = `
        <div class="modal-content investment-main-menu">
            <div class="modal-header">
                <h2>Investment Strategies</h2>
                <button class="btn-close-modal" onclick="closeInvestmentModal()">×</button>
            </div>
            
            <div class="investment-menu-buttons">
                <button class="menu-btn snake-btn" onclick="showSnakeStrategies()">
                    <div class="menu-icon"><i class="fas fa-skull"></i></div>
                    <div class="menu-text">
                        <h3>Snake Investment Strategies</h3>
                        <p>Protect against threats</p>
                    </div>
                    <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
                </button>
                
                <button class="menu-btn ladder-btn" onclick="showLadderStrategies()">
                    <div class="menu-icon"><i class="fas fa-ladder"></i></div>
                    <div class="menu-text">
                        <h3>Ladder Investment Strategies</h3>
                        <p>Exploit opportunities</p>
                    </div>
                    <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
                </button>
            </div>

            <div class="investment-footer">
                <div class="capital-display">
                    <span>Available Capital:</span>
                    <strong class="capital-value">${calculateProjectedCapital()}</strong>
                </div>
                <button class="btn-confirm-final" onclick="finalizeAllInvestments()">
                    Confirm Investment
                </button>
            </div>
        </div>
    `;

  document.body.appendChild(modal);
  GameState.isInvesting = false;
}

/**
 * Show Snake Strategies Sub-view
 */
function showSnakeStrategies() {
  console.log("Showing Snake Strategies");
  renderStrategySubView('threats', 'Snake Investment Strategies');
}

/**
 * Show Ladder Strategies Sub-view
 */
function showLadderStrategies() {
  console.log("Showing Ladder Strategies");
  renderStrategySubView('opportunities', 'Ladder Investment Strategies');
}

/**
 * Render Strategy Sub-view (Generic)
 */
function renderStrategySubView(type, title) {
  try {
    console.log(`Rendering sub-view: ${type}`);
    // Use ID to ensure we target the correct modal
    const modal = document.getElementById("investmentModal");
    if (!modal) {
      console.error("Investment modal not found");
      return;
    }

    const modalContent = modal.querySelector('.modal-content');
    if (!modalContent) {
      console.error("Modal content not found");
      return;
    }

    if (!GameState.investmentData) {
      console.error("Investment data missing");
      showMessage("Error: Strategy data not loaded.", "error");
      return;
    }

    const items = GameState.investmentData[type];

    if (!items || items.length === 0) {
      console.warn(`No items found for type: ${type}`);
      showMessage(`No ${title} available.`, 'info');
      return;
    }

    let itemsHTML = '';

    items.forEach(item => {
      let strategiesHTML = '';
      if (item.strategies && item.strategies.length > 0) {
        item.strategies.forEach(strategy => {
          const stratId = strategy.id;
          const points = parseInt(strategy.response_points);
          const isChecked = GameState.pendingInvestments[stratId] ? 'checked' : '';

          strategiesHTML += `
                    <div class="sub-strategy-item" onclick="toggleStrategyCheckbox(this)">
                        <div class="check-container">
                            <input type="checkbox" 
                                class="sub-strategy-checkbox" 
                                data-id="${stratId}" 
                                data-points="${points}" 
                                ${isChecked}>
                        </div>
                        <div class="strategy-details">
                            <div class="strategy-name">${strategy.strategy_name}</div>
                            <div class="strategy-desc">${strategy.description || ''}</div>
                        </div>
                        <div class="strategy-cost">${points} pts</div>
                    </div>
                `;
        });
      } else {
        strategiesHTML = '<div class="no-strategies">No strategies available for this item.</div>';
      }

      itemsHTML += `
            <div class="group-item">
                <div class="group-header">
                    <div class="group-path">
                        <span class="path-label">${type === 'threats' ? 'Path' : 'Climb'}:</span> 
                        <span class="badge cell-badge">${item.cell_from}</span> 
                        <i class="fas fa-long-arrow-alt-right"></i> 
                        <span class="badge cell-badge">${item.cell_to}</span>
                    </div>
                    <div class="group-name">${item.name}</div>
                </div>
                <div class="group-strategies">
                    ${strategiesHTML}
                </div>
            </div>
        `;
    });

    modalContent.innerHTML = `
        <div class="modal-header">
            <button class="btn-back" onclick="showInvestmentMainMenu()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <h2>${title}</h2>
            <button class="btn-close-modal" onclick="closeInvestmentModal()">×</button>
        </div>
        <div class="sub-view-scroll-area">
            ${itemsHTML}
        </div>
        <div class="investment-footer">
            <div class="capital-display">
                <span>Remaining:</span>
                <strong class="capital-value" id="subViewCapital">${calculateProjectedCapital()}</strong>
            </div>
            <button class="btn-invest-sub" onclick="saveSubViewInvestments()">
                Invest & Return
            </button>
        </div>
    `;

    // Add event listeners for checkboxes to update local capital display
    document.querySelectorAll('.sub-strategy-checkbox').forEach(cb => {
      cb.addEventListener('change', updateSubViewCapital);
    });

  } catch (error) {
    console.error(`Error in renderStrategySubView (${type}):`, error);
    showMessage("An error occurred while loading strategies.", "error");
  }
}

/**
 * Toggle Checkbox on Row Click
 */
function toggleStrategyCheckbox(element) {
  // Avoid double triggering if clicking directly on checkbox
  if (event.target.type === 'checkbox') return;

  const checkbox = element.querySelector('input[type="checkbox"]');
  checkbox.checked = !checkbox.checked;

  // Trigger change event manually
  const eventChange = new Event('change');
  checkbox.dispatchEvent(eventChange);
}

/**
 * Update Sub-view Capital Display
 */
function updateSubViewCapital() {
  // Sync currently visible checkboxes into GameState.pendingInvestments
  document.querySelectorAll('.sub-strategy-checkbox').forEach(cb => {
    const stratId = cb.dataset.id;
    const points = parseInt(cb.dataset.points);
    if (cb.checked) {
      GameState.pendingInvestments[stratId] = points;
    } else {
      delete GameState.pendingInvestments[stratId];
    }
  });

  const projected = calculateProjectedCapital();
  const capDisplay = document.getElementById('subViewCapital');
  if (capDisplay) {
    capDisplay.textContent = projected;
    capDisplay.style.color = projected < 0 ? '#e74c3c' : '#27ae60';
  }
}

/**
 * Calculate Projected Capital (Global)
 */
function calculateProjectedCapital() {
  // We start from the absolute basis (Capital + Previous Investments)
  let basis = parseInt(GameState.capitalRemaining);

  const prevInvs = GameState.playerInvestments;
  if (Array.isArray(prevInvs)) {
    prevInvs.forEach(inv => {
      basis += parseInt(inv.investment_points || inv.points || 0);
    });
  } else if (prevInvs && typeof prevInvs === 'object') {
    Object.values(prevInvs).forEach(inv => {
      basis += parseInt(inv.investment_points || inv.points || 0);
    });
  }

  let pendingUsed = 0;
  if (GameState.pendingInvestments) {
    for (const points of Object.values(GameState.pendingInvestments)) {
      pendingUsed += points;
    }
  }

  return basis - pendingUsed;
}

/**
 * Save Sub-view Investments (Local State)
 */
function saveSubViewInvestments() {
  // Update pendingInvestments with current view's state
  document.querySelectorAll('.sub-strategy-checkbox').forEach(cb => {
    if (cb.checked) {
      GameState.pendingInvestments[cb.dataset.id] = parseInt(cb.dataset.points);
    } else {
      delete GameState.pendingInvestments[cb.dataset.id];
    }
  });

  // Validate Budget
  if (calculateProjectedCapital() < 0) {
    showMessage("Insufficient capital! Please deselect some strategies.", "error");
    return;
  }

  showMessage("Selections updated.", "success");
  showInvestmentMainMenu();
}

/**
 * Finalize All Investments (Push to Server)
 */
function finalizeAllInvestments() {
  const confirmBtn = document.querySelector('.btn-confirm-final');
  if (confirmBtn) {
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Processing...';
  }

  const selectedStrategies = [];
  for (const [id, points] of Object.entries(GameState.pendingInvestments)) {
    selectedStrategies.push({
      strategy_id: id,
      points: points
    });
  }

  // Final Budget Check using CalculateProjectedCapital
  if (calculateProjectedCapital() < 0) {
    showMessage("Insufficient capital! Please adjust your selections.", "error");
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.textContent = 'Confirm Investment';
    }
    return;
  }

  // Send to server
  fetch("ajax/invest_strategy.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      session_id: GameState.sessionId,
      strategies: selectedStrategies,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        GameState.capitalRemaining = data.capital_remaining;
        GameState.playerInvestments = data.investments;
        closeInvestmentModal();
        updateGameUI();

        // Disable main investment button per requirements
        disableInvestmentButton();

        showMessage("All investments confirmed successfully!", "success");
      } else {
        showMessage(data.message, "error");
        if (confirmBtn) {
          confirmBtn.disabled = false;
          confirmBtn.textContent = 'Confirm Investment';
        }
      }
    })
    .catch((error) => {
      console.error("Error saving investment:", error);
      showMessage("Error saving investment. Please try again.", "error");
      if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirm Investment';
      }
    });
}

/**
 * Close investment modal
 */
function closeInvestmentModal() {
  const modal = document.getElementById("investmentModal");
  if (modal) {
    modal.remove();
  }
}

/**
 * Handle cell hover
 */
function handleCellHover(e) {
  const cell = e.currentTarget;
  const cellNumber = parseInt(cell.dataset.cell);

  // Track current hover
  GameState.currentlyHoveredCell = cellNumber;

  // Fetch cell info
  fetch("ajax/get_cell_info.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      matrix_id: GameState.matrixId,
      cell_number: cellNumber,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      const res = data.data || data;
      if (data.success && res.cell_info) {
        showCellTooltip(cell, res.cell_info);
      }
    });
}

/**
 * Show cell tooltip
 */
function showCellTooltip(cell, cellInfo) {
  // Verify we are still hovering the correct cell
  if (GameState.currentlyHoveredCell !== parseInt(cell.dataset.cell)) {
    return;
  }

  // Remove existing tooltip manually to avoid resetting hover state
  const existingTooltip = document.getElementById("cellTooltip");
  if (existingTooltip) {
    existingTooltip.remove();
  }

  // Null checks
  if (!cellInfo || !cellInfo.type) {
    // console.warn("Invalid cellInfo provided to showCellTooltip:", cellInfo);
    return;
  }

  const tooltip = document.createElement("div");
  tooltip.className = "cell-tooltip";
  tooltip.id = "cellTooltip";
  // Initial state for animation
  tooltip.style.opacity = '0';
  tooltip.style.transform = 'translateY(10px) scale(0.95)';
  tooltip.style.transition = 'opacity 0.2s ease-out, transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275)';

  let content = "";

  switch (cellInfo.type) {
    case "threat":
      if (cellInfo.data && cellInfo.data.threat_name) {
        content = `<strong>Snake: ${cellInfo.data.threat_name}</strong><br>`;
        content += `From Cell ${cellInfo.data.cell_from} → ${cellInfo.data.cell_to}`;
        if (cellInfo.strategies && cellInfo.strategies.length > 0) {
          content += '<div class="tooltip-strategies">Required Strategies:';
          cellInfo.strategies.forEach((s) => {
            if (s && s.strategy_name && s.response_points) {
              content += `<div>• ${s.strategy_name} (${s.response_points} pts)</div>`;
            }
          });
          content += "</div>";
        }
      }
      break;

    case "opportunity":
      if (cellInfo.data && cellInfo.data.opportunity_name) {
        content = `<strong>Ladder: ${cellInfo.data.opportunity_name}</strong><br>`;
        content += `From Cell ${cellInfo.data.cell_from} → ${cellInfo.data.cell_to}`;
        if (cellInfo.strategies && cellInfo.strategies.length > 0) {
          content += '<div class="tooltip-strategies">Required Strategies:';
          cellInfo.strategies.forEach((s) => {
            if (s && s.strategy_name && s.response_points) {
              content += `<div>• ${s.strategy_name} (${s.response_points} pts)</div>`;
            }
          });
          content += "</div>";
        }
      }
      break;

    case "bonus":
      if (cellInfo.data && cellInfo.data.bonus_amount) {
        content = `<strong>Bonus Cell</strong><br>+${cellInfo.data.bonus_amount} Risk Capital`;
      }
      break;

    case "audit":
      content = `<strong>Audit Cell</strong><br>Review your strategy investments`;
      break;

    case "wildcard":
      content = `<strong>Wild Card</strong><br>Random event`;
      break;

    default:
      return; // Don't show tooltip for neutral cells
  }

  if (content) {
    tooltip.innerHTML = content;
    document.body.appendChild(tooltip);

    // Position tooltip
    const cellRect = cell.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();

    // Calculate centered position above the cell
    let left = cellRect.left + (cellRect.width / 2) - (tooltipRect.width / 2);
    let top = cellRect.top - tooltipRect.height - 15; // 15px gap

    // Prevent going off-screen (Left edge)
    if (left < 10) left = 10;

    // Prevent going off-screen (Right edge)
    if (left + tooltipRect.width > window.innerWidth - 10) {
      left = window.innerWidth - tooltipRect.width - 10;
    }

    // Prevent going off-screen (Top edge) - Flip to bottom if needed
    if (top < 10) {
      top = cellRect.bottom + 15;
    }

    tooltip.style.left = left + "px";
    tooltip.style.top = top + "px";

    // Add show class for transition
    requestAnimationFrame(() => {
      tooltip.style.opacity = '1';
      tooltip.style.transform = 'translateY(0) scale(1)';
    });
  }
}


/**
 * Hide cell tooltip
 */
function hideCellTooltip() {
  GameState.currentlyHoveredCell = null;
  const tooltip = document.getElementById("cellTooltip");
  if (tooltip) {
    tooltip.remove();
  }
}

/**
 * Update button states
 */
function updateButtonStates() {
  const rollBtn = document.getElementById("rollDiceBtn");
  const investBtn = document.getElementById("investBtn");

  // Roll dice button
  if (rollBtn) {
    rollBtn.disabled = GameState.diceRemaining <= 0 || GameState.isRolling;
  }

  // Check if game is over
  const totalCells = (GameState.gameData && GameState.gameData.game) ? parseInt(GameState.gameData.game.total_cells) : 0;

  if (
    GameState.diceRemaining <= 0 ||
    (totalCells > 0 && GameState.currentCell >= totalCells)
  ) {
    endGame();
  }
}

/**
 * Enable investment button
 */
function enableInvestmentButton() {
  const investBtn = document.getElementById("investBtn");
  if (investBtn) {
    investBtn.disabled = false;
  }
}

/**
 * Disable investment button
 */
function disableInvestmentButton() {
  const investBtn = document.getElementById("investBtn");
  if (investBtn) {
    investBtn.disabled = true;
  }
}

/**
 * Calculate protection for threat
 */
function calculateProtection(threatId) {
  // This would need the threat strategies and player investments
  // Simplified version
  return 0; // Placeholder
}

/**
 * Calculate exploitation for opportunity
 */
function calculateExploitation(opportunityId) {
  // This would need the opportunity strategies and player investments
  // Simplified version
  return 0; // Placeholder
}

/**
 * Continue game (resume)
 */
function continueGame() {
  // Implementation for continuing saved game
  alert("Continue game feature");
}

/**
 * Pause game
 */
function pauseGame() {
  const confirmed = confirm(
    "Do you want to pause the game? You can continue later.",
  );
  if (confirmed) {
    window.location.href = "index.php";
  }
}

/**
 * Confirm exit
 */
function confirmExit() {
  const confirmed = confirm(
    "Are you sure you want to exit? Your game progress will be reset.",
  );
  if (confirmed) {
    // Call server to clear/reset game session
    fetch("ajax/exit_game.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        session_id: GameState.sessionId
      })
    }).then(() => {
      window.location.href = "index.php";
    }).catch(() => {
      // Fallback if fetch fails
      window.location.href = "index.php";
    });
  }
}

/**
 * Show instructions popup
 */
function showInstructions() {
  const gameId = GameState.matrixId;
  const url = gameId ? `instruction.php?game_id=${gameId}` : "instruction.php";
  window.open(url, "instructions", "width=800,height=600,scrollbars=yes");
}

/**
 * End game
 */
function endGame() {
  fetch("ajax/end_game.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      session_id: GameState.sessionId,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log('📊 End game response:', JSON.stringify(data));
      if (data.success) {
        // json_response wraps in data.data, statistics is inside that
        const responseData = data.data || data;
        const stats = responseData.statistics || data.statistics || {};
        console.log('📊 Extracted stats:', JSON.stringify(stats));
        showGameOverModal(stats);
      }
    });
}

/**
 * Show game over modal
 */
function showGameOverModal(stats) {
  // Null check for statistics
  if (!stats) {
    console.error("Missing statistics for game over modal");
    stats = {
      max_cell_reached: GameState.currentCell,
      total_cells: (GameState.gameData && GameState.gameData.game) ? parseInt(GameState.gameData.game.total_cells) : 100,
      game_score: 0
    };
  }
  // Ensure defaults for all properties to avoid 'undefined'
  stats = {
    max_cell_reached: parseInt(stats.max_cell_reached) || GameState.currentCell || 0,
    total_cells: parseInt(stats.total_cells) || ((GameState.gameData && GameState.gameData.game) ? parseInt(GameState.gameData.game.total_cells) : 100),
    total_dice_used: stats.total_dice_used || 0,
    threats_protected: stats.threats_protected || 0,
    threats_total: stats.threats_total || 0,
    opportunities_exploited: stats.opportunities_exploited || 0,
    opportunities_total: stats.opportunities_total || 0,
    wildcards_opened: stats.wildcards_opened || 0,
    final_capital: stats.final_capital || 0
  };

  // Determine Win/Loss: Use server-provided flag if available, otherwise compare numerically
  const isWin = typeof stats.is_win !== 'undefined' ?
    stats.is_win :
    (parseInt(stats.max_cell_reached) >= parseInt(stats.total_cells));

  if (isWin) {
    AudioManager.playWin();
  } else {
    AudioManager.playLoss();
  }
  const modal = document.createElement("div");
  modal.className = "modal-overlay show"; // Added 'show' class
  modal.innerHTML = `
        <div class="modal-content">
            <div class="game-over-content">
                <div class="game-result-icon ${isWin ? "win" : "lose"}">
                    ${isWin ? "🏆" : "🎮"}
                </div>
                <h2>${isWin ? "Congratulations!" : "Game Over"}</h2>
                <p>${isWin ? "Congratulations! You won the match!" : "Better luck next time!"}</p>
                
                <div class="game-statistics">
                    <div class="stat-box">
                        <div class="stat-box-value">${stats.max_cell_reached}/${stats.total_cells}</div>
                        <div class="stat-box-label">Cells Reached</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-value">${stats.total_dice_used}</div>
                        <div class="stat-box-label">Dice Used</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-value">${stats.threats_protected}/${stats.threats_total}</div>
                        <div class="stat-box-label">Threats Protected</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-value">${stats.opportunities_exploited}/${stats.opportunities_total}</div>
                        <div class="stat-box-label">Opportunities Exploited</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-value">${stats.wildcards_opened}</div>
                        <div class="stat-box-label">Wildcards Opened</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-value">${stats.final_capital}</div>
                        <div class="stat-box-label">Final Capital</div>
                    </div>
                </div>
                
                <div class="game-over-actions">
                    <button class="btn-play-again" onclick="playAgain()">Play Again</button>
                    <button class="btn-exit-game" onclick="exitToLibrary()">Exit</button>
                </div>
            </div>
        </div>
    `;

  document.body.appendChild(modal);
}

/**
 * Play again
 */
function playAgain() {
  window.location.href = `play.php?game_id=${GameState.matrixId}`;
}

/**
 * Exit to library
 */
function exitToLibrary() {
  window.location.href = "index.php";
}

/**
 * Show toast message
 */
function showMessage(message, type = 'info', duration = 4000) {
  // Remove existing notifications if too many
  const notifications = document.querySelectorAll('.game-notification');
  if (notifications.length >= 3) {
    if (notifications[0] && notifications[0].parentElement) {
      notifications[0].remove();
    }
  }
  // Remove existing notification
  const existing = document.querySelector('.game-notification');
  if (existing) existing.remove();

  const notification = document.createElement('div');
  notification.className = `game-notification ${type}`;

  let icon = 'info-circle';
  let title = 'Information';

  if (type === 'success') {
    icon = 'check-circle';
    title = 'Success!';
  } else if (type === 'error') {
    icon = 'times-circle'; // changed from exclamation-circle for error
    title = 'Error';
  } else if (type === 'warning') {
    icon = 'exclamation-triangle';
    title = 'Warning';
  }

  // Handle new lines in message
  const formattedMessage = message.replace(/\n/g, '<br>');

  notification.innerHTML = `
        <div class="notification-icon">
            <i class="fas fa-${icon}"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">${title}</div>
            <div class="notification-message">${formattedMessage}</div>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

  document.body.appendChild(notification);

  // Trigger animation
  setTimeout(() => {
    notification.classList.add('show');
  }, 10);

  // Auto remove after specified duration
  const removeTimeout = duration || 4000;

  setTimeout(() => {
    if (notification && notification.parentElement) {
      notification.classList.remove('show');
      setTimeout(() => {
        if (notification && notification.parentElement) {
          notification.remove();
        }
      }, 500);
    }
  }, removeTimeout);
}

// Mobile touch handling
if ("ontouchstart" in window) {
  document.addEventListener("touchstart", function () { }, { passive: true });
} 