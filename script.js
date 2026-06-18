// script.js - Complete JavaScript with all enhancements
(function() {
    // State management
    let state = {
        entries: [],
        user: null,
        token: null,
        currentView: 'all', // all, by_tag, shared
        currentTag: null,
        selectedEntry: null
    };
    
    // API Configuration
    const API_BASE = 'api/';
    
    // DOM References
    const elements = {
        grid: document.getElementById('portfolioGrid'),
        entryCount: document.getElementById('entryCount'),
        searchInput: document.getElementById('searchInput'),
        clearSearchBtn: document.getElementById('clearSearchBtn'),
        modalOverlay: document.getElementById('modalOverlay'),
        openModalBtn: document.getElementById('openModalBtn'),
        cancelModalBtn: document.getElementById('cancelModalBtn'),
        entryForm: document.getElementById('entryForm'),
        titleInput: document.getElementById('titleInput'),
        categoryInput: document.getElementById('categoryInput'),
        descInput: document.getElementById('descInput'),
        imageInput: document.getElementById('imageInput'),
        tagsInput: document.getElementById('tagsInput'),
        loginModal: document.getElementById('loginModal'),
        loginForm: document.getElementById('loginForm'),
        registerForm: document.getElementById('registerForm'),
        usernameInput: document.getElementById('usernameInput'),
        passwordInput: document.getElementById('passwordInput'),
        emailInput: document.getElementById('emailInput'),
        fullNameInput: document.getElementById('fullNameInput'),
        userInfo: document.getElementById('userInfo'),
        logoutBtn: document.getElementById('logoutBtn'),
        commentModal: document.getElementById('commentModal'),
        commentForm: document.getElementById('commentForm'),
        commentsList: document.getElementById('commentsList'),
        commentInput: document.getElementById('commentInput'),
        shareModal: document.getElementById('shareModal'),
        shareLink: document.getElementById('shareLink'),
        copyShareLink: document.getElementById('copyShareLink'),
        exportBtn: document.getElementById('exportBtn'),
        tagCloud: document.getElementById('tagCloud'),
        analyticsContainer: document.getElementById('analyticsContainer'),
        viewDetailsBtn: document.getElementById('viewDetailsBtn')
    };
    
    // --- AUTHENTICATION ---
    function login(username, password) {
        return fetch(API_BASE + 'auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                state.token = data.token;
                state.user = data.user;
                localStorage.setItem('token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
                updateUI();
                loadEntries();
                loadTags();
                loadAnalytics();
                showNotification('Welcome back, ' + data.user.username + '!');
            } else {
                showNotification(data.error || 'Login failed', 'error');
            }
            return data;
        });
    }
    
    function register(username, email, password, fullName) {
        return fetch(API_BASE + 'auth.php?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, email, password, full_name: fullName })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('Registration successful! Please login.');
            } else {
                showNotification(data.error || 'Registration failed', 'error');
            }
            return data;
        });
    }
    
    function logout() {
        state.token = null;
        state.user = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        updateUI();
        loadEntries();
        showNotification('Logged out successfully');
    }
    
    function checkAuth() {
        const token = localStorage.getItem('token');
        const user = JSON.parse(localStorage.getItem('user') || 'null');
        if (token && user) {
            state.token = token;
            state.user = user;
            updateUI();
            loadEntries();
            loadTags();
            loadAnalytics();
            return true;
        }
        return false;
    }
    
    // --- API FUNCTIONS ---
    function getHeaders() {
        return {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + state.token
        };
    }
    
    function loadEntries() {
        const url = state.currentView === 'by_tag' && state.currentTag 
            ? `tags.php?action=by_tag&tag=${encodeURIComponent(state.currentTag)}`
            : 'get_entries.php';
            
        fetch(API_BASE + url, {
            headers: getHeaders()
        })
        .then(res => res.json())
        .then(data => {
            state.entries = data;
            renderGrid();
        })
        .catch(err => {
            console.error('Error loading entries:', err);
            showNotification('Failed to load entries', 'error');
        });
    }
    
    function addEntry(title, category, description, tags, imageFile) {
        const formData = new FormData();
        formData.append('title', title);
        formData.append('category', category);
        formData.append('description', description);
        formData.append('tags', tags);
        if (imageFile) formData.append('image', imageFile);
        
        return fetch(API_BASE + 'add_entry.php', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + state.token },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadEntries();
                showNotification('Entry added successfully!');
            } else {
                showNotification(data.error || 'Failed to add entry', 'error');
            }
            return data;
        });
    }
    
    function deleteEntry(id) {
        if (!confirm('Delete this entry?')) return Promise.reject('Cancelled');
        
        return fetch(API_BASE + 'delete_entry.php?id=' + id, {
            method: 'DELETE',
            headers: getHeaders()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadEntries();
                showNotification('Entry deleted');
            } else {
                showNotification(data.error || 'Failed to delete', 'error');
            }
            return data;
        });
    }
    
    function searchEntries(query) {
        return fetch(API_BASE + 'search.php?q=' + encodeURIComponent(query), {
            headers: getHeaders()
        })
        .then(res => res.json())
        .then(data => data.entries || []);
    }
    
    function loadTags() {
        fetch(API_BASE + 'tags.php?action=all', {
            headers: getHeaders()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderTagCloud(data.tags);
            }
        })
        .catch(err => console.error('Error loading tags:', err));
    }
    
    function loadComments(entryId) {
        return fetch(API_BASE + `comments.php?action=get&entry_id=${entryId}`, {
            headers: getHeaders()
        })
        .then(res => res.json())
        .then(data => data.comments || []);
    }
    
    function addComment(entryId, content) {
        return fetch(API_BASE + 'comments.php?action=add', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ entry_id: entryId, content })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('Comment added');
                return data.comment;
            } else {
                showNotification(data.error || 'Failed to add comment', 'error');
                return null;
            }
        });
    }
    
    function loadAnalytics() {
        if (!state.token) return;
        
        fetch(API_BASE + 'analytics.php?action=stats', {
            headers: getHeaders()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderAnalytics(data);
            }
        })
        .catch(err => console.error('Error loading analytics:', err));
    }
    
    function generateShareLink(entryId) {
        return fetch(API_BASE + 'share.php?action=generate', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ entry_id: entryId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                return data.share_url;
            } else {
                showNotification(data.error || 'Failed to generate share link', 'error');
                return null;
            }
        });
    }
    
    function exportPortfolio(format) {
        window.open(API_BASE + `export.php?format=${format}&user_id=${state.user.id}`, '_blank');
    }
    
    // --- RENDER FUNCTIONS ---
    function renderGrid() {
        const entries = state.entries || [];
        elements.entryCount.textContent = entries.length;
        elements.grid.innerHTML = '';
        
        if (entries.length === 0) {
            elements.grid.innerHTML = `
                <div class="empty-state">
                    <i class="fas ${state.currentTag ? 'fa-tag' : 'fa-image'}"></i>
                    <p>${state.currentTag ? 'No entries with tag "' + state.currentTag + '"' : 'No portfolio items yet. Add your first artwork!'}</p>
                </div>
            `;
            return;
        }
        
        entries.forEach(entry => {
            const card = document.createElement('div');
            card.className = 'portfolio-card';
            
            card.innerHTML = `
                <div class="card-icon">
                    <i class="fas ${getIconForCategory(entry.category)}"></i>
                </div>
                <div class="card-title">${escapeHTML(entry.title)}</div>
                ${entry.category ? `<span class="card-category">${escapeHTML(entry.category)}</span>` : ''}
                <div class="card-desc">${entry.description ? escapeHTML(entry.description) : '—'}</div>
                <div class="card-tags">
                    ${entry.tags ? entry.tags.split(',').map(tag => 
                        `<span class="tag">#${escapeHTML(tag.trim())}</span>`
                    ).join('') : ''}
                </div>
                <div class="card-meta">
                    <span><i class="far fa-clock"></i> ${new Date(entry.created_at).toLocaleDateString()}</span>
                    <span><i class="far fa-eye"></i> ${entry.views || 0}</span>
                    <span><i class="far fa-heart"></i> ${entry.likes || 0}</span>
                </div>
                <div class="card-actions">
                    <button class="action-btn comment-btn" data-id="${entry.id}">
                        <i class="fas fa-comment"></i> Comments
                    </button>
                    <button class="action-btn share-btn" data-id="${entry.id}">
                        <i class="fas fa-share-alt"></i>
                    </button>
                    <button class="action-btn delete-btn" data-id="${entry.id}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `;
            
            // Event listeners
            card.querySelector('.comment-btn').addEventListener('click', () => openComments(entry.id));
            card.querySelector('.share-btn').addEventListener('click', () => openShare(entry.id));
            card.querySelector('.delete-btn').addEventListener('click', () => deleteEntry(entry.id));
            
            // Click on card to view details
            card.addEventListener('click', (e) => {
                if (e.target.closest('.card-actions')) return;
                openEntryDetails(entry);
            });
            
            elements.grid.appendChild(card);
        });
    }
    
    function renderTagCloud(tags) {
        if (!tags || tags.length === 0) {
            elements.tagCloud.innerHTML = '<span style="color: #999;">No tags yet</span>';
            return;
        }
        
        elements.tagCloud.innerHTML = tags.map(tag => `
            <span class="tag-cloud-item" data-tag="${escapeHTML(tag)}">
                #${escapeHTML(tag)}
            </span>
        `).join('');
        
        elements.tagCloud.querySelectorAll('.tag-cloud-item').forEach(el => {
            el.addEventListener('click', () => {
                state.currentTag = el.dataset.tag;
                state.currentView = 'by_tag';
                loadEntries();
                showNotification('Showing entries with tag: #' + state.currentTag);
            });
        });
    }
    
    function renderAnalytics(data) {
        if (!data.stats) return;
        
        elements.analyticsContainer.innerHTML = `
            <div class="analytics-grid">
                <div class="stat-card">
                    <div class="stat-number">${data.stats.total_entries || 0}</div>
                    <div class="stat-label">Total Entries</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${data.stats.total_views || 0}</div>
                    <div class="stat-label">Total Views</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${data.stats.total_likes || 0}</div>
                    <div class="stat-label">Total Likes</div>
                </div>
            </div>
            <div class="top-entries">
                <h4>Top Performing Entries</h4>
                ${data.top_entries.map(entry => `
                    <div class="top-entry">
                        <span>${escapeHTML(entry.title)}</span>
                        <span>👁️ ${entry.views} ❤️ ${entry.likes}</span>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    // --- UI HELPERS ---
    function getIconForCategory(category) {
        const icons = {
            'animation': 'fa-film',
            '3d': 'fa-cube',
            'illustration': 'fa-paint-brush',
            'digital': 'fa-pen-fancy',
            'design': 'fa-object-group',
            'photography': 'fa-camera'
        };
        if (!category) return 'fa-image';
        const key = Object.keys(icons).find(k => category.toLowerCase().includes(k));
        return key ? icons[key] : 'fa-image';
    }
    
    function escapeHTML(text) {
        if (!text) return '';
        return text.replace(/[&<>"]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            if (m === '"') return '&quot;';
            return m;
        });
    }
    
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }
    
    function updateUI() {
        if (state.user) {
            elements.userInfo.innerHTML = `
                <span><i class="fas fa-user"></i> ${escapeHTML(state.user.username)}</span>
                <button id="logoutBtn" class="btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</button>
            `;
            document.getElementById('logoutBtn').addEventListener('click', logout);
            elements.openModalBtn.style.display = 'flex';
        } else {
            elements.userInfo.innerHTML = `
                <button id="loginBtn" class="btn-sm"><i class="fas fa-sign-in-alt"></i> Login</button>
                <button id="registerBtn" class="btn-sm btn-secondary">Register</button>
            `;
            document.getElementById('loginBtn').addEventListener('click', openLoginModal);
            document.getElementById('registerBtn').addEventListener('click', openRegisterModal);
            elements.openModalBtn.style.display = 'none';
        }
    }
    
    // --- MODAL CONTROLS ---
    function openLoginModal() {
        // Implement login modal
        elements.loginModal.classList.add('active');
    }
    
    function openRegisterModal() {
        // Implement register modal
        elements.registerForm.style.display = 'block';
        elements.loginForm.style.display = 'none';
        elements.loginModal.classList.add('active');
    }
    
    function openComments(entryId) {
        // Implement comments modal
        elements.commentModal.classList.add('active');
        elements.commentModal.dataset.entryId = entryId;
        loadComments(entryId).then(comments => {
            elements.commentsList.innerHTML = comments.map(c => `
                <div class="comment-item">
                    <strong>${escapeHTML(c.username || 'User')}</strong>
                    <p>${escapeHTML(c.content)}</p>
                    <small>${new Date(c.created_at).toLocaleString()}</small>
                </div>
            `).join('');
        });
    }
    
    function openShare(entryId) {
        generateShareLink(entryId).then(url => {
            if (url) {
                elements.shareLink.value = url;
                elements.shareModal.classList.add('active');
            }
        });
    }
    
    function openEntryDetails(entry) {
        // Implement entry details view
        // Can be a modal or separate page
        console.log('Viewing entry:', entry);
    }
    
    // --- EVENT LISTENERS ---
    // Add entry form
    elements.entryForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const title = elements.titleInput.value.trim();
        if (!title) {
            showNotification('Title is required', 'error');
            return;
        }
        
        addEntry(
            title,
            elements.categoryInput.value.trim(),
            elements.descInput.value.trim(),
            elements.tagsInput.value.trim(),
            elements.imageInput.files[0]
        ).then(() => {
            elements.modalOverlay.classList.remove('active');
            this.reset();
        });
    });
    
    // Search
    let searchTimeout;
    elements.searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const query = this.value.trim();
            if (query) {
                searchEntries(query).then(entries => {
                    state.entries = entries;
                    renderGrid();
                });
            } else {
                state.currentView = 'all';
                state.currentTag = null;
                loadEntries();
            }
        }, 300);
    });
    
    // Copy share link
    elements.copyShareLink.addEventListener('click', function() {
        elements.shareLink.select();
        document.execCommand('copy');
        showNotification('Link copied to clipboard!');
    });
    
    // Export
    elements.exportBtn.addEventListener('click', function() {
        const format = prompt('Export format (json, csv, html):', 'json');
        if (format) exportPortfolio(format);
    });
    
    // Comment submit
    elements.commentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const entryId = elements.commentModal.dataset.entryId;
        const content = elements.commentInput.value.trim();
        if (!content) {
            showNotification('Comment cannot be empty', 'error');
            return;
        }
        addComment(entryId, content).then(() => {
            elements.commentInput.value = '';
            loadComments(entryId).then(comments => {
                // Refresh comments display
            });
        });
    });
    
    // Modal close buttons
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal-overlay').classList.remove('active');
        });
    });
    
    // Close modals on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    });
    
    // --- INIT ---
    function init() {
        // Check authentication
        if (!checkAuth()) {
            // Show login prompt
        }
        
        // Load initial data
        loadEntries();
        loadTags();
        
        // Setup UI
        updateUI();
        
        // Add CSS for notifications
        const style = document.createElement('style');
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 9999;
                animation: slideIn 0.3s ease;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            }
            .notification.success { background: #2d7d46; }
            .notification.error { background: #c0392b; }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            .tag-cloud-item {
                display: inline-block;
                padding: 4px 12px;
                background: #f0ece5;
                border-radius: 20px;
                margin: 3px;
                cursor: pointer;
                transition: 0.2s;
                font-size: 13px;
            }
            .tag-cloud-item:hover {
                background: #2d2a24;
                color: white;
                transform: scale(1.05);
            }
            .card-tags {
                margin: 8px 0;
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
            }
            .card-tags .tag {
                background: #e7e2db;
                padding: 2px 10px;
                border-radius: 12px;
                font-size: 11px;
                color: #4a443c;
            }
            .card-actions {
                display: flex;
                gap: 8px;
                margin-top: 12px;
                padding-top: 12px;
                border-top: 1px solid #ede8e0;
            }
            .action-btn {
                background: none;
                border: none;
                color: #7b756b;
                cursor: pointer;
                padding: 4px 10px;
                border-radius: 20px;
                font-size: 12px;
                transition: 0.2s;
            }
            .action-btn:hover {
                background: #f0ece5;
                color: #2d2a24;
            }
            .action-btn.delete-btn:hover {
                background: #fde8e5;
                color: #c0392b;
            }
            .btn-sm {
                padding: 5px 15px;
                border: none;
                border-radius: 20px;
                background: #2d2a24;
                color: white;
                cursor: pointer;
                font-size: 13px;
                transition: 0.2s;
            }
            .btn-sm:hover { background: #1f1c17; }
            .btn-secondary { background: #e7e2db; color: #2d2a24; }
            .analytics-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            .stat-card {
                background: white;
                padding: 20px;
                border-radius: 12px;
                text-align: center;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            .stat-number {
                font-size: 32px;
                font-weight: 700;
                color: #2d2a24;
            }
            .stat-label {
                color: #7b756b;
                font-size: 14px;
                margin-top: 5px;
            }
            .top-entries {
                margin-top: 20px;
                background: white;
                padding: 20px;
                border-radius: 12px;
            }
            .top-entry {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #f0ece5;
            }
            .top-entry:last-child { border-bottom: none; }
        `;
        document.head.appendChild(style);
    }
    
    // Start the app
    init();
})();