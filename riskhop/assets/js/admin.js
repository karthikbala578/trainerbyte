

// THREAT (SNAKE) FUNCTIONS


function openThreatModal() {
  document.getElementById("threat_id").value = "";
  document.getElementById("threat_name").value = "";
  document.getElementById("threat_description").value = "";
  document.getElementById("threat_cell_from").value = "";
  document.getElementById("threat_cell_to").value = "";
  document.getElementById("threatModalTitle").textContent =
    "Add Threat (Snake)";
  document.getElementById("threatSubmitBtn").textContent = "Add Threat";
  document.getElementById("threatModal").style.display = "flex";
}

function closeThreatModal() {
  document.getElementById("threatModal").style.display = "none";
}

function editThreat(id, name, desc, from, to) {
  document.getElementById("threat_id").value = id;
  document.getElementById("threat_name").value = name;
  document.getElementById("threat_description").value = desc;
  document.getElementById("threat_cell_from").value = from;
  document.getElementById("threat_cell_to").value = to;
  document.getElementById("threatModalTitle").textContent =
    "Edit Threat (Snake)";
  document.getElementById("threatSubmitBtn").textContent = "Update Threat";
  document.getElementById("threatModal").style.display = "flex";
}

// Threat form submission handler
if (document.getElementById("threatForm")) {
  document
    .getElementById("threatForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const submitBtn = document.getElementById("threatSubmitBtn");
      const isEdit = document.getElementById("threat_id").value !== "";

      submitBtn.disabled = true;
      submitBtn.textContent = isEdit ? "Updating..." : "Adding...";

      const url = isEdit ? "ajax/edit_threat.php" : "ajax/add_threat.php";

      fetch(url, {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert(
              isEdit
                ? "Threat updated successfully!"
                : "Threat added successfully!",
            );
            location.reload();
          } else {
            alert(data.message || "An error occurred. Please try again.");
            submitBtn.disabled = false;
            submitBtn.textContent = isEdit ? "Update Threat" : "Add Threat";
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again.");
          submitBtn.disabled = false;
          submitBtn.textContent = isEdit ? "Update Threat" : "Add Threat";
        });
    });
}

function deleteThreat(threatId) {
  if (confirm("Delete this threat? Associated strategies will be removed.")) {
    fetch("ajax/delete_threat.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "threat_id=" + threatId,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Threat deleted successfully!");
          location.reload();
        } else {
          alert(data.message || "Failed to delete threat.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
      });
  }
}

// OPPORTUNITY (LADDER) FUNCTIONS
  
function openOpportunityModal() {
  document.getElementById("opportunity_id").value = "";
  document.getElementById("opportunity_name").value = "";
  document.getElementById("opportunity_description").value = "";
  document.getElementById("opportunity_cell_from").value = "";
  document.getElementById("opportunity_cell_to").value = "";
  document.getElementById("opportunityModalTitle").textContent =
    "Add Opportunity (Ladder)";
  document.getElementById("opportunitySubmitBtn").textContent =
    "Add Opportunity";
  document.getElementById("opportunityModal").style.display = "flex";
}

function closeOpportunityModal() {
  document.getElementById("opportunityModal").style.display = "none";
}

function editOpportunity(id, name, desc, from, to) {
  document.getElementById("opportunity_id").value = id;
  document.getElementById("opportunity_name").value = name;
  document.getElementById("opportunity_description").value = desc;
  document.getElementById("opportunity_cell_from").value = from;
  document.getElementById("opportunity_cell_to").value = to;
  document.getElementById("opportunityModalTitle").textContent =
    "Edit Opportunity (Ladder)";
  document.getElementById("opportunitySubmitBtn").textContent =
    "Update Opportunity";
  document.getElementById("opportunityModal").style.display = "flex";
}

// Opportunity form submission handler
if (document.getElementById("opportunityForm")) {
  document
    .getElementById("opportunityForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const submitBtn = document.getElementById("opportunitySubmitBtn");
      const isEdit = document.getElementById("opportunity_id").value !== "";

      submitBtn.disabled = true;
      submitBtn.textContent = isEdit ? "Updating..." : "Adding...";

      const url = isEdit
        ? "ajax/edit_opportunity.php"
        : "ajax/add_opportunity.php";

      fetch(url, {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert(
              isEdit
                ? "Opportunity updated successfully!"
                : "Opportunity added successfully!",
            );
            location.reload();
          } else {
            alert(data.message || "An error occurred. Please try again.");
            submitBtn.disabled = false;
            submitBtn.textContent = isEdit
              ? "Update Opportunity"
              : "Add Opportunity";
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again.");
          submitBtn.disabled = false;
          submitBtn.textContent = isEdit
            ? "Update Opportunity"
            : "Add Opportunity";
        });
    });
}

function deleteOpportunity(opportunityId) {
  if (
    confirm("Delete this opportunity? Associated strategies will be removed.")
  ) {
    fetch("ajax/delete_opportunity.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "opportunity_id=" + opportunityId,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Opportunity deleted successfully!");
          location.reload();
        } else {
          alert(data.message || "Failed to delete opportunity.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
      });
  }
}

// STRATEGY FUNCTIONS
   
function openStrategyModal(riskType, riskId, riskName) {
  document.getElementById("strategy_risk_id").value = riskId;
  document.getElementById("strategy_risk_type").value = riskType;
  document.getElementById("strategyModalTitle").textContent =
    "Strategies for: " + riskName;

  loadExistingStrategies(riskType, riskId);

  document.getElementById("strategyModal").style.display = "flex";
}

function closeStrategyModal() {
  document.getElementById("strategyModal").style.display = "none";
  document.getElementById("strategyForm").reset();
  const checkboxes = document.querySelectorAll(".existing-strategy-check");
  if (checkboxes) {
    checkboxes.forEach((cb) => (cb.checked = false));
  }
}

function loadExistingStrategies(riskType, riskId) {
  fetch("ajax/get_strategies.php?risk_type=" + riskType + "&risk_id=" + riskId)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        let html =
          '<h4 style="font-size: 20px; margin-bottom: 15px;">Current Strategies</h4>';
        if (data.data.strategies.length > 0) {
          html += '<div style="max-height: 200px; overflow-y: auto;">';
          data.data.strategies.forEach((strategy) => {
            html += `<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 10px; background: #f9fafb;">
                        <div>
                            <strong style="font-size: 16px;">${strategy.strategy_name}</strong><br>
                            <small style="color: var(--secondary-color);">${strategy.description}</small><br>
                            <span class="badge" style="background:#dbeafe;color:#1e40af;padding:4px 10px;border-radius:6px;font-size:13px;margin-top:5px;display:inline-block;">Points: ${strategy.response_points}</span>
                        </div>
                        <button class="btn btn-sm btn-danger" onclick="removeStrategy('${riskType}', ${riskId}, ${strategy.id})">Remove</button>
                    </div>`;
          });
          html += "</div>";
        } else {
          html +=
            '<p style="color: var(--secondary-color); font-size: 15px;">No strategies added yet.</p>';
        }
        document.getElementById("existingStrategies").innerHTML = html;
      }
    })
    .catch((error) => {
      console.error("Error loading strategies:", error);
    });
}

// Strategy form submission handler
if (document.getElementById("strategyForm")) {
  document
    .getElementById("strategyForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append(
        "risk_id",
        document.getElementById("strategy_risk_id").value,
      );
      formData.append(
        "risk_type",
        document.getElementById("strategy_risk_type").value,
      );

      const submitBtn = this.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.textContent = "Adding...";

      fetch("ajax/add_strategy.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert("Strategy added successfully!");
            closeStrategyModal();
            location.reload();
          } else {
            alert(data.message || "Failed to add strategy.");
            submitBtn.disabled = false;
            submitBtn.textContent = "Add Strategy";
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again.");
          submitBtn.disabled = false;
          submitBtn.textContent = "Add Strategy";
        });
    });
}

function mapExistingStrategies() {
  const checkboxes = document.querySelectorAll(
    ".existing-strategy-check:checked",
  );
  const strategyIds = Array.from(checkboxes).map((cb) => cb.value);

  if (strategyIds.length === 0) {
    alert("Please select at least one strategy to map");
    return;
  }

  const riskType = document.getElementById("strategy_risk_type").value;
  const riskId = document.getElementById("strategy_risk_id").value;

  fetch("ajax/map_existing_strategies.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      risk_type: riskType,
      risk_id: riskId,
      strategy_ids: strategyIds,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Strategies mapped successfully!");
        closeStrategyModal();
        location.reload();
      } else {
        alert(data.message || "Failed to map strategies.");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("An error occurred. Please try again.");
    });
}

function removeStrategy(riskType, riskId, strategyId) {
  if (confirm("Remove this strategy from the risk?")) {
    fetch("ajax/remove_strategy.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `risk_type=${riskType}&risk_id=${riskId}&strategy_id=${strategyId}`,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Strategy removed successfully!");
          closeStrategyModal();
          location.reload();
        } else {
          alert(data.message || "Failed to remove strategy.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
      });
  }
}
   // BONUS CELL FUNCTIONS
   
function openBonusModal() {
  document.getElementById("bonusModal").style.display = "flex";
  document.getElementById("bonusModalTitle").textContent = "Add Bonus Cell";
  document.getElementById("bonusSubmitBtn").textContent = "Add Bonus";
  document.getElementById("bonus_id").value = "";
  document.getElementById("bonusForm").reset();
}

function editBonus(bonusId, cellNumber, bonusAmount) {
  document.getElementById("bonusModal").style.display = "flex";
  document.getElementById("bonusModalTitle").textContent = "Edit Bonus Cell";
  document.getElementById("bonusSubmitBtn").textContent = "Update Bonus";
  document.getElementById("bonus_id").value = bonusId;
  document.getElementById("bonus_cell_number").value = cellNumber;
  document.getElementById("bonus_amount").value = bonusAmount;
}

function closeBonusModal() {
  document.getElementById("bonusModal").style.display = "none";
  document.getElementById("bonusForm").reset();
  document.getElementById("bonus_id").value = "";
}

// Bonus form submission handler
if (document.getElementById("bonusForm")) {
  document.getElementById("bonusForm").addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const bonusId = document.getElementById("bonus_id").value;
    const isEdit = bonusId !== "";

    submitBtn.disabled = true;
    submitBtn.textContent = isEdit ? "Updating..." : "Adding...";

    const ajaxUrl = isEdit ? "ajax/edit_bonus.php" : "ajax/add_bonus.php";

    fetch(ajaxUrl, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert(
            isEdit
              ? "Bonus cell updated successfully!"
              : "Bonus cell added successfully!",
          );
          location.reload();
        } else {
          alert(data.message || "An error occurred. Please try again.");
          submitBtn.disabled = false;
          submitBtn.textContent = isEdit ? "Update Bonus" : "Add Bonus";
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
        submitBtn.disabled = false;
        submitBtn.textContent = isEdit ? "Update Bonus" : "Add Bonus";
      });
  });
}

function deleteBonus(bonusId) {
  if (confirm("Delete this bonus cell?")) {
    fetch("ajax/delete_bonus.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "bonus_id=" + bonusId,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Bonus cell deleted successfully!");
          location.reload();
        } else {
          alert(data.message || "Failed to delete bonus cell.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
      });
  }
}

   // AUDIT CELL FUNCTIONS
   
function openAuditModal() {
  document.getElementById("auditModal").style.display = "flex";
}

function closeAuditModal() {
  document.getElementById("auditModal").style.display = "none";
  document.getElementById("auditForm").reset();
}

if (document.getElementById("auditForm")) {
  document.getElementById("auditForm").addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = "Adding...";

    fetch("ajax/add_audit.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Audit cell added successfully!");
          location.reload();
        } else {
          alert(data.message || "Failed to add audit cell.");
          submitBtn.disabled = false;
          submitBtn.textContent = "Add Audit";
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
        submitBtn.disabled = false;
        submitBtn.textContent = "Add Audit";
      });
  });
}

function deleteAudit(auditId) {
  if (confirm("Delete this audit cell?")) {
    fetch("ajax/delete_audit.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "audit_id=" + auditId,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Audit cell deleted successfully!");
          location.reload();
        } else {
          alert(data.message || "Failed to delete audit cell.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
      });
  }
}

   // WILDCARD CELL FUNCTIONS
   
function openWildcardCellModal() {
  document.getElementById("wildcardCellModal").style.display = "flex";
}

function closeWildcardCellModal() {
  document.getElementById("wildcardCellModal").style.display = "none";
  document.getElementById("wildcardCellForm").reset();
}

if (document.getElementById("wildcardCellForm")) {
  document
    .getElementById("wildcardCellForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const submitBtn = this.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.textContent = "Adding...";

      fetch("ajax/add_wildcard_cell.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert("Wildcard cell added successfully!");
            location.reload();
          } else {
            alert(data.message || "Failed to add wildcard cell.");
            submitBtn.disabled = false;
            submitBtn.textContent = "Add Wildcard Cell";
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again.");
          submitBtn.disabled = false;
          submitBtn.textContent = "Add Wildcard Cell";
        });
    });
}

function deleteWildcardCell(wildcardCellId) {
  if (confirm("Delete this wildcard cell?")) {
    fetch("ajax/delete_wildcard_cell.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "wildcard_cell_id=" + wildcardCellId,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Wildcard cell deleted successfully!");
          location.reload();
        } else {
          alert(data.message || "Failed to delete wildcard cell.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
      });
  }
}

// WILDCARD OPTION FUNCTIONS

function openWildcardOptionModal() {
  document.getElementById("wildcard_id").value = "";
  document.getElementById("wildcard_name").value = "";
  document.getElementById("wildcard_description").value = "";
  document.getElementById("risk_capital_effect").value = "0";
  document.getElementById("dice_effect").value = "0";
  document.getElementById("cell_effect").value = "0";
  document.getElementById("wildcardOptionModalTitle").textContent =
    "Add Wildcard Option";
  document.getElementById("wildcardOptionSubmitBtn").textContent =
    "Add Wildcard Option";
  document.getElementById("wildcardOptionModal").style.display = "flex";
}

function closeWildcardOptionModal() {
  document.getElementById("wildcardOptionModal").style.display = "none";
  document.getElementById("wildcardOptionForm").reset();
}

function editWildcardOption(id, name, desc, riskCap, dice, cell) {
  document.getElementById("wildcard_id").value = id;
  document.getElementById("wildcard_name").value = name;
  document.getElementById("wildcard_description").value = desc;
  document.getElementById("risk_capital_effect").value = riskCap;
  document.getElementById("dice_effect").value = dice;
  document.getElementById("cell_effect").value = cell;
  document.getElementById("wildcardOptionModalTitle").textContent =
    "Edit Wildcard Option";
  document.getElementById("wildcardOptionSubmitBtn").textContent =
    "Update Wildcard Option";
  document.getElementById("wildcardOptionModal").style.display = "flex";
}

if (document.getElementById("wildcardOptionForm")) {
  document
    .getElementById("wildcardOptionForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const submitBtn = document.getElementById("wildcardOptionSubmitBtn");
      const isEdit = document.getElementById("wildcard_id").value !== "";

      submitBtn.disabled = true;
      submitBtn.textContent = isEdit ? "Updating..." : "Adding...";

      const url = isEdit
        ? "ajax/edit_wildcard_option.php"
        : "ajax/add_wildcard_option.php";

      fetch(url, {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert(
              isEdit
                ? "Wildcard option updated successfully!"
                : "Wildcard option added successfully!",
            );
            location.reload();
          } else {
            alert(data.message || "An error occurred. Please try again.");
            submitBtn.disabled = false;
            submitBtn.textContent = isEdit
              ? "Update Wildcard Option"
              : "Add Wildcard Option";
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again.");
          submitBtn.disabled = false;
          submitBtn.textContent = isEdit
            ? "Update Wildcard Option"
            : "Add Wildcard Option";
        });
    });
}

function deleteWildcardOption(wildcardId) {
  if (confirm("Delete this wildcard option?")) {
    fetch("ajax/delete_wildcard_option.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "wildcard_id=" + wildcardId,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Wildcard option deleted successfully!");
          location.reload();
        } else {
          alert(data.message || "Failed to delete wildcard option.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
      });
  }
}

   // BOARD PREVIEW RENDERING
   
function renderBoardPreview(matrixId, matrixType) {
  fetch("ajax/get_board_data.php?matrix_id=" + matrixId)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const container = document.getElementById("boardPreview");
        const gridClass = "grid-" + matrixType.replace("x", "x");

        let html = `<div class="board-grid ${gridClass}">`;

        const totalCells = data.total_cells;
        const threats = data.threats || [];
        const opportunities = data.opportunities || [];
        const bonuses = data.bonuses || [];
        const audits = data.audits || [];
        const wildcards = data.wildcards || [];

        // Render cells
        for (let i = totalCells; i >= 1; i--) {
          let cellClass = "board-cell";
          let cellContent = i;
          let cellIcon = "";

          // Check if threat
          const threat = threats.find(
            (t) => t.cell_from == i || t.cell_to == i,
          );
          if (threat) {
            if (threat.cell_from == i) {
              cellClass += " snake";
              cellIcon = "🐍";
            } else {
              cellClass += " snake";
              cellIcon = "⬇️";
            }
          }

          // Check if opportunity
          const opportunity = opportunities.find(
            (o) => o.cell_from == i || o.cell_to == i,
          );
          if (opportunity) {
            if (opportunity.cell_from == i) {
              cellClass += " ladder";
              cellIcon = "⬆️";
            } else {
              cellClass += " ladder";
              cellIcon = "🪜";
            }
          }

          // Check if bonus
          if (bonuses.find((b) => b.cell_number == i)) {
            cellClass += " bonus";
            cellIcon = "💰";
          }

          // Check if audit
          if (audits.find((a) => a.cell_number == i)) {
            cellClass += " audit";
            cellIcon = "📋";
          }

          // Check if wildcard
          if (wildcards.find((w) => w.cell_number == i)) {
            cellClass += " wildcard";
            cellIcon = "🎴";
          }

          html += `<div class="${cellClass}" title="Cell ${i}">
                    ${cellIcon ? '<span class="cell-icon">' + cellIcon + "</span>" : ""}
                    <div>${cellContent}</div>
                </div>`;
        }

        html += "</div>";

        // Add arrows for snakes and ladders
        html += renderArrows(threats, opportunities, matrixType);

        container.innerHTML = html;
      }
    })
    .catch((error) => {
      console.error("Error loading board preview:", error);
    });
}

function renderArrows(threats, opportunities, matrixType) {
  let arrowsHtml = '<div class="board-arrows">';

  // Red arrows for threats (snakes)
  threats.forEach((threat) => {
    arrowsHtml += `<div class="arrow-label snake-arrow">
            Snake: ${threat.cell_from} → ${threat.cell_to}
        </div>`;
  });

  // Green arrows for opportunities (ladders)
  opportunities.forEach((opp) => {
    arrowsHtml += `<div class="arrow-label ladder-arrow">
            Ladder: ${opp.cell_from} → ${opp.cell_to}
        </div>`;
  });

  arrowsHtml += "</div>";
  return arrowsHtml;
}

   // UTILITY FUNCTIONS
   
// Close modals when clicking outside
window.onclick = function (event) {
  const modals = document.querySelectorAll(".modal");
  modals.forEach((modal) => {
    if (event.target === modal) {
      modal.style.display = "none";
    }
  });
};

// Form validation helper
function validateNumber(input, min, max) {
  const value = parseInt(input.value);
  if (isNaN(value) || value < min || value > max) {
    input.setCustomValidity(`Please enter a number between ${min} and ${max}`);
    return false;
  }
  input.setCustomValidity("");
  return true;
}
