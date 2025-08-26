<?php
/**
 * Debug Panel UI
 * Tro365 - Website thuê trọ
 */

if (!isDebugModeEnabled()) return;

$performance = $debugData['performance'] ?? [];
$queries = $debugData['queries'] ?? [];
$errors = $debugData['errors'] ?? [];
$apiCalls = $debugData['api_calls'] ?? [];
$serverInfo = $debugData['server_info'] ?? [];
$requestInfo = $debugData['request_info'] ?? [];
?>

<!-- Debug Panel CSS -->
<style>
.tro365-debug-panel {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.95);
    color: #fff;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    z-index: 99999;
    border-top: 3px solid #0d6efd;
    max-height: 50vh;
    overflow: hidden;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.tro365-debug-panel.collapsed {
    max-height: 40px;
}

.debug-header {
    background: #0d6efd;
    padding: 8px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.debug-title {
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 10px;
}

.debug-stats {
    display: flex;
    gap: 15px;
    font-size: 11px;
}

.debug-content {
    max-height: calc(50vh - 40px);
    overflow-y: auto;
}

.debug-tabs {
    display: flex;
    background: #1a1a1a;
    border-bottom: 1px solid #333;
}

.debug-tab {
    padding: 8px 15px;
    cursor: pointer;
    border-right: 1px solid #333;
    transition: background 0.2s;
    position: relative;
}

.debug-tab:hover {
    background: #333;
}

.debug-tab.active {
    background: #0d6efd;
}

.debug-tab-badge {
    background: #dc3545;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 10px;
    margin-left: 5px;
}

.debug-tab-content {
    display: none;
    padding: 15px;
    max-height: 300px;
    overflow-y: auto;
}

.debug-tab-content.active {
    display: block;
}

.debug-metric {
    display: inline-block;
    background: rgba(255, 255, 255, 0.1);
    padding: 2px 6px;
    border-radius: 3px;
    margin-right: 5px;
}

.debug-query {
    background: #2a2a2a;
    margin: 5px 0;
    padding: 10px;
    border-radius: 4px;
    border-left: 3px solid #0d6efd;
}

.debug-error {
    background: #2a1a1a;
    margin: 5px 0;
    padding: 10px;
    border-radius: 4px;
    border-left: 3px solid #dc3545;
}

.debug-api-call {
    background: #1a2a1a;
    margin: 5px 0;
    padding: 10px;
    border-radius: 4px;
    border-left: 3px solid #28a745;
}

.debug-close {
    background: none;
    border: none;
    color: white;
    font-size: 16px;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.debug-toggle {
    background: none;
    border: none;
    color: white;
    font-size: 14px;
    cursor: pointer;
    margin-right: 10px;
}

.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }
.text-danger { color: #dc3545 !important; }
.text-info { color: #17a2b8 !important; }
.text-muted { color: #6c757d !important; }

@media (max-width: 768px) {
    .tro365-debug-panel {
        font-size: 10px;
    }
    
    .debug-stats {
        flex-direction: column;
        gap: 5px;
    }
    
    .debug-tabs {
        flex-wrap: wrap;
    }
    
    .debug-tab {
        flex: 1;
        text-align: center;
        min-width: 80px;
    }
}
</style>

<!-- Debug Panel HTML -->
<div class="tro365-debug-panel" id="debugPanel">
    <div class="debug-header" onclick="toggleDebugPanel()">
        <div class="debug-title">
            <i class="fas fa-bug"></i>
            <span>Trọ 365 Debug Panel</span>
        </div>
        <div class="debug-stats">
            <span class="debug-metric">
                <i class="fas fa-clock"></i> <?= $performance['execution_time'] ?? 0 ?>ms
            </span>
            <span class="debug-metric">
                <i class="fas fa-memory"></i> <?= $performance['memory_usage'] ?? '0B' ?>
            </span>
            <span class="debug-metric">
                <i class="fas fa-database"></i> <?= count($queries) ?> queries
            </span>
            <?php if (count($errors) > 0): ?>
            <span class="debug-metric text-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= count($errors) ?> errors
            </span>
            <?php endif; ?>
        </div>
        <div>
            <button class="debug-toggle"
                    onclick="event.stopPropagation(); toggleDebugPanel()"
                    aria-label="Thu gọn/Mở rộng debug panel"
                    title="Thu gọn/Mở rộng debug panel">
                <i class="fas fa-chevron-up" id="debugToggleIcon"></i>
            </button>
            <button class="debug-close"
                    onclick="event.stopPropagation(); closeDebugPanel()"
                    aria-label="Đóng debug panel"
                    title="Đóng debug panel">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <div class="debug-content" id="debugContent">
        <div class="debug-tabs" role="tablist">
            <div class="debug-tab active"
                 id="performance-tab"
                 onclick="showDebugTab('performance')"
                 role="tab"
                 aria-selected="true"
                 aria-controls="debug-performance"
                 tabindex="0">
                <i class="fas fa-tachometer-alt"></i> Performance
            </div>
            <div class="debug-tab"
                 id="queries-tab"
                 onclick="showDebugTab('queries')"
                 role="tab"
                 aria-selected="false"
                 aria-controls="debug-queries"
                 tabindex="-1">
                <i class="fas fa-database"></i> Queries
                <?php if (count($queries) > 0): ?>
                <span class="debug-tab-badge"><?= count($queries) ?></span>
                <?php endif; ?>
            </div>
            <?php if (count($errors) > 0): ?>
            <div class="debug-tab"
                 id="errors-tab"
                 onclick="showDebugTab('errors')"
                 role="tab"
                 aria-selected="false"
                 aria-controls="debug-errors"
                 tabindex="-1">
                <i class="fas fa-exclamation-triangle"></i> Errors
                <span class="debug-tab-badge"><?= count($errors) ?></span>
            </div>
            <?php endif; ?>
            <?php if (count($apiCalls) > 0): ?>
            <div class="debug-tab"
                 id="api-tab"
                 onclick="showDebugTab('api')"
                 role="tab"
                 aria-selected="false"
                 aria-controls="debug-api"
                 tabindex="-1">
                <i class="fas fa-exchange-alt"></i> API
                <span class="debug-tab-badge"><?= count($apiCalls) ?></span>
            </div>
            <?php endif; ?>
            <div class="debug-tab"
                 id="request-tab"
                 onclick="showDebugTab('request')"
                 role="tab"
                 aria-selected="false"
                 aria-controls="debug-request"
                 tabindex="-1">
                <i class="fas fa-globe"></i> Request
            </div>
            <div class="debug-tab"
                 id="server-tab"
                 onclick="showDebugTab('server')"
                 role="tab"
                 aria-selected="false"
                 aria-controls="debug-server"
                 tabindex="-1">
                <i class="fas fa-server"></i> Server
            </div>
        </div>
        
        <!-- Performance Tab -->
        <div class="debug-tab-content active"
             id="debug-performance"
             role="tabpanel"
             aria-labelledby="performance-tab">
            <h4><i class="fas fa-tachometer-alt text-info"></i> Performance Metrics</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                <div><strong>Execution Time:</strong> <span class="text-info"><?= $performance['execution_time'] ?? 0 ?>ms</span></div>
                <div><strong>Memory Usage:</strong> <span class="text-warning"><?= $performance['memory_usage'] ?? '0B' ?></span></div>
                <div><strong>Peak Memory:</strong> <span class="text-danger"><?= $performance['memory_peak'] ?? '0B' ?></span></div>
                <div><strong>Memory Diff:</strong> <span class="text-success"><?= $performance['memory_diff'] ?? '0B' ?></span></div>
                <div><strong>Database Queries:</strong> <span class="text-info"><?= $performance['queries_count'] ?? 0 ?></span></div>
                <div><strong>Errors:</strong> <span class="<?= ($performance['errors_count'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>"><?= $performance['errors_count'] ?? 0 ?></span></div>
            </div>
        </div>
        
        <!-- Queries Tab -->
        <div class="debug-tab-content"
             id="debug-queries"
             role="tabpanel"
             aria-labelledby="queries-tab">
            <h4><i class="fas fa-database text-info"></i> Database Queries (<?= count($queries) ?>)</h4>
            <?php if (empty($queries)): ?>
                <p class="text-muted">No database queries executed.</p>
            <?php else: ?>
                <?php foreach ($queries as $index => $query): ?>
                <div class="debug-query">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <strong>Query #<?= $index + 1 ?></strong>
                        <span class="text-warning"><?= $query['execution_time'] ?>ms</span>
                    </div>
                    <code style="color: #61dafb;"><?= htmlspecialchars($query['sql']) ?></code>
                    <?php if (!empty($query['params'])): ?>
                    <div style="margin-top: 5px;">
                        <small class="text-muted">Parameters:</small>
                        <code style="color: #98d982;"><?= htmlspecialchars(json_encode($query['params'], JSON_UNESCAPED_UNICODE)) ?></code>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Errors Tab -->
        <?php if (count($errors) > 0): ?>
        <div class="debug-tab-content"
             id="debug-errors"
             role="tabpanel"
             aria-labelledby="errors-tab">
            <h4><i class="fas fa-exclamation-triangle text-danger"></i> Errors (<?= count($errors) ?>)</h4>
            <?php foreach ($errors as $index => $error): ?>
            <div class="debug-error">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <strong>Error #<?= $index + 1 ?></strong>
                    <span class="text-muted"><?= $error['file'] ?>:<?= $error['line'] ?></span>
                </div>
                <div class="text-danger"><?= htmlspecialchars($error['message']) ?></div>
                <?php if (!empty($error['context'])): ?>
                <div style="margin-top: 5px;">
                    <small class="text-muted">Context:</small>
                    <code style="color: #ffc107;"><?= htmlspecialchars(json_encode($error['context'], JSON_UNESCAPED_UNICODE)) ?></code>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- API Tab -->
        <?php if (count($apiCalls) > 0): ?>
        <div class="debug-tab-content"
             id="debug-api"
             role="tabpanel"
             aria-labelledby="api-tab">
            <h4><i class="fas fa-exchange-alt text-success"></i> API Calls (<?= count($apiCalls) ?>)</h4>
            <?php foreach ($apiCalls as $index => $call): ?>
            <div class="debug-api-call">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <strong><?= $call['method'] ?> <?= htmlspecialchars($call['url']) ?></strong>
                    <span class="text-info"><?= $call['execution_time'] ?>ms</span>
                </div>
                <div>Status: <span class="<?= $call['status_code'] >= 200 && $call['status_code'] < 300 ? 'text-success' : 'text-danger' ?>"><?= $call['status_code'] ?></span></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Request Tab -->
        <div class="debug-tab-content"
             id="debug-request"
             role="tabpanel"
             aria-labelledby="request-tab">
            <h4><i class="fas fa-globe text-info"></i> Request Information</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;">
                <div><strong>Method:</strong> <span class="text-info"><?= $requestInfo['method'] ?? 'Unknown' ?></span></div>
                <div><strong>URI:</strong> <span class="text-warning"><?= htmlspecialchars($requestInfo['uri'] ?? '') ?></span></div>
                <div><strong>Query String:</strong> <span class="text-muted"><?= htmlspecialchars($requestInfo['query_string'] ?? 'None') ?></span></div>
                <div><strong>User Agent:</strong> <span class="text-muted" style="font-size: 10px;"><?= htmlspecialchars(substr($requestInfo['user_agent'] ?? '', 0, 50)) ?>...</span></div>
                <div><strong>Remote IP:</strong> <span class="text-success"><?= $requestInfo['remote_addr'] ?? 'Unknown' ?></span></div>
                <div><strong>Request Time:</strong> <span class="text-info"><?= date('Y-m-d H:i:s', (int)($requestInfo['request_time'] ?? time())) ?></span></div>
            </div>
        </div>
        
        <!-- Server Tab -->
        <div class="debug-tab-content"
             id="debug-server"
             role="tabpanel"
             aria-labelledby="server-tab">
            <h4><i class="fas fa-server text-info"></i> Server Information</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;">
                <div><strong>PHP Version:</strong> <span class="text-success"><?= $serverInfo['php_version'] ?? 'Unknown' ?></span></div>
                <div><strong>Server Software:</strong> <span class="text-info"><?= htmlspecialchars($serverInfo['server_software'] ?? 'Unknown') ?></span></div>
                <div><strong>Document Root:</strong> <span class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($serverInfo['document_root'] ?? '') ?></span></div>
                <div><strong>Script Name:</strong> <span class="text-warning"><?= htmlspecialchars($serverInfo['script_name'] ?? '') ?></span></div>
            </div>
        </div>
    </div>
</div>

<!-- Debug Panel JavaScript -->
<script>
let debugPanelCollapsed = localStorage.getItem('tro365-debug-collapsed') === 'true';

function toggleDebugPanel() {
    const panel = document.getElementById('debugPanel');
    const icon = document.getElementById('debugToggleIcon');
    
    debugPanelCollapsed = !debugPanelCollapsed;
    
    if (debugPanelCollapsed) {
        panel.classList.add('collapsed');
        icon.className = 'fas fa-chevron-down';
    } else {
        panel.classList.remove('collapsed');
        icon.className = 'fas fa-chevron-up';
    }
    
    localStorage.setItem('tro365-debug-collapsed', debugPanelCollapsed);
}

function closeDebugPanel() {
    document.getElementById('debugPanel').style.display = 'none';
    // Only set closed state temporarily, don't persist it
    sessionStorage.setItem('tro365-debug-closed-session', 'true');
}

function showDebugTab(tabName) {
    // Hide all tab contents and update aria-selected
    document.querySelectorAll('.debug-tab-content').forEach(content => {
        content.classList.remove('active');
    });

    // Remove active class from all tabs and update accessibility attributes
    document.querySelectorAll('.debug-tab').forEach(tab => {
        tab.classList.remove('active');
        tab.setAttribute('aria-selected', 'false');
        tab.setAttribute('tabindex', '-1');
    });

    // Show selected tab content
    const selectedContent = document.getElementById('debug-' + tabName);
    if (selectedContent) {
        selectedContent.classList.add('active');
    }

    // Add active class to selected tab and update accessibility
    const selectedTab = event.target.closest('.debug-tab');
    if (selectedTab) {
        selectedTab.classList.add('active');
        selectedTab.setAttribute('aria-selected', 'true');
        selectedTab.setAttribute('tabindex', '0');
        selectedTab.focus();
    }
}

// Initialize panel state
document.addEventListener('DOMContentLoaded', function() {
    // Check session storage first (temporary close)
    if (sessionStorage.getItem('tro365-debug-closed-session') === 'true') {
        document.getElementById('debugPanel').style.display = 'none';
    } else if (debugPanelCollapsed) {
        toggleDebugPanel();
    }

    // Clear any old localStorage debug closed state
    localStorage.removeItem('tro365-debug-closed');
});

// Add keyboard navigation for debug tabs
document.addEventListener('keydown', function(e) {
    // Debug panel toggle shortcut
    if (e.ctrlKey && e.shiftKey && e.key === 'D') {
        e.preventDefault();
        const panel = document.getElementById('debugPanel');
        if (panel.style.display === 'none') {
            panel.style.display = 'block';
            sessionStorage.removeItem('tro365-debug-closed-session');
        } else {
            toggleDebugPanel();
        }
        return;
    }

    // Tab navigation within debug panel
    if (e.target.classList.contains('debug-tab')) {
        const tabs = Array.from(document.querySelectorAll('.debug-tab'));
        const currentIndex = tabs.indexOf(e.target);
        let nextIndex = -1;

        switch(e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                nextIndex = currentIndex > 0 ? currentIndex - 1 : tabs.length - 1;
                break;
            case 'ArrowRight':
                e.preventDefault();
                nextIndex = currentIndex < tabs.length - 1 ? currentIndex + 1 : 0;
                break;
            case 'Home':
                e.preventDefault();
                nextIndex = 0;
                break;
            case 'End':
                e.preventDefault();
                nextIndex = tabs.length - 1;
                break;
            case 'Enter':
            case ' ':
                e.preventDefault();
                e.target.click();
                return;
        }

        if (nextIndex >= 0 && tabs[nextIndex]) {
            tabs[nextIndex].focus();
        }
    }
});
</script>
