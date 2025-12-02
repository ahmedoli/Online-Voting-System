/**
 * Real-time Results JavaScript for Live Vote Updates
 */

class RealTimeResults {
    constructor(electionId = null, refreshInterval = 5000) {
        this.electionId = electionId;
        this.refreshInterval = refreshInterval;
        this.isActive = false;
        this.intervalId = null;
    }

    // Start real-time updates
    start() {
        if (this.isActive) return;
        
        this.isActive = true;
        this.updateResults(); // Initial load
        
        // Set up periodic updates
        this.intervalId = setInterval(() => {
            this.updateResults();
        }, this.refreshInterval);
        

    }

    // Stop real-time updates
    stop() {
        if (!this.isActive) return;
        
        this.isActive = false;
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        

    }

    // Fetch and update results
    async updateResults() {
        try {
            const response = await fetch(`../includes/get_live_results.php${this.electionId ? '?election_id=' + this.electionId : ''}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.renderResults(data.results);
                this.updateLastRefresh();
            } else {
                console.error('Error fetching results:', data.message);
            }
        } catch (error) {
            console.error('Network error:', error);
            // Don't stop on network errors, just log them
        }
    }

    // Render results in the DOM
    renderResults(results) {
        results.forEach(election => {
            const container = document.querySelector(`[data-election-id="${election.id}"]`);
            if (!container) return;

            const resultsContainer = container.querySelector('.live-results');
            if (!resultsContainer) return;

            let html = `
                <div class="results-header">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-chart-bar me-2"></i>Live Results - ${election.name}
                    </h6>
                </div>
                <div class="candidates-results">
            `;

            election.candidates.forEach((candidate, index) => {
                const percentage = election.total_votes > 0 ? 
                    Math.round((candidate.votes / election.total_votes) * 100) : 0;
                
                const isLeader = index === 0 && candidate.votes > 0;
                
                html += `
                    <div class="candidate-result mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="candidate-info">
                                <strong class="${isLeader ? 'text-success' : ''}">${candidate.name}</strong>
                                ${candidate.party ? `<small class="text-muted ms-2">${candidate.party}</small>` : ''}
                                ${isLeader ? '<i class="fas fa-crown text-warning ms-1"></i>' : ''}
                            </div>
                            <div class="vote-count">
                                <span class="badge ${isLeader ? 'bg-success' : 'bg-primary'}">${candidate.votes} votes</span>
                                <small class="text-muted ms-1">${percentage}%</small>
                            </div>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar ${isLeader ? 'bg-success' : 'bg-primary'}" 
                                 role="progressbar" 
                                 style="width: ${percentage}%"
                                 aria-valuenow="${percentage}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                </div>
                <div class="results-summary mt-3 p-2 bg-light rounded">
                    <small class="text-muted">
                        <i class="fas fa-users me-1"></i>Total Votes: <strong>${election.total_votes}</strong>
                        <span class="ms-3">
                            <i class="fas fa-sync-alt me-1"></i>Last Updated: <span class="last-refresh">Just now</span>
                        </span>
                    </small>
                </div>
            `;

            resultsContainer.innerHTML = html;
        });
    }

    // Update last refresh timestamp
    updateLastRefresh() {
        const elements = document.querySelectorAll('.last-refresh');
        const now = new Date().toLocaleTimeString();
        elements.forEach(el => {
            el.textContent = now;
        });
    }

    // Toggle real-time updates
    toggle() {
        if (this.isActive) {
            this.stop();
        } else {
            this.start();
        }
        return this.isActive;
    }
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on a page that needs real-time results
    const resultsContainers = document.querySelectorAll('.live-results-container');
    
    if (resultsContainers.length > 0) {
        // Initialize real-time results
        window.realTimeResults = new RealTimeResults();
        
        // Add toggle button if not exists
        addRealTimeToggleButton();
        
        // Auto-start real-time updates
        window.realTimeResults.start();
    }
});

// Add toggle button for real-time results
function addRealTimeToggleButton() {
    const container = document.querySelector('.results-controls');
    if (container && !document.querySelector('.realtime-toggle')) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn btn-outline-success btn-sm realtime-toggle ms-2';
        toggleBtn.innerHTML = '<i class="fas fa-play me-1"></i>Live Updates ON';
        toggleBtn.onclick = function() {
            const isActive = window.realTimeResults.toggle();
            this.innerHTML = isActive ? 
                '<i class="fas fa-pause me-1"></i>Live Updates ON' : 
                '<i class="fas fa-play me-1"></i>Live Updates OFF';
            this.className = `btn btn-outline-${isActive ? 'success' : 'secondary'} btn-sm realtime-toggle ms-2`;
        };
        container.appendChild(toggleBtn);
    }
}