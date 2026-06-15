<style>
/* Enhanced Search Styles */
.header-search {
    position: relative;
    max-width: 500px;
    width: 100%;
}

.header-search .form-control {
    border-radius: 25px;
    padding-left: 3rem;
    padding-right: 3rem;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    background: #fff;
}

.header-search .form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    background: #fff;
}

.header-search .form-control::placeholder {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Search Results Dropdown */
.search-results-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    z-index: 1050;
    max-height: 500px;
    overflow-y: auto;
    margin-top: 8px;
}

.search-results-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 12px 12px 0 0;
}

.search-results-title {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
}

.search-results-count {
    font-size: 0.8rem;
    font-weight: 500;
}

.search-results-content {
    max-height: 350px;
    overflow-y: auto;
}

.search-results-footer {
    padding: 1rem;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 0 0 12px 12px;
}

/* Search Categories */
.search-category {
    border-bottom: 1px solid #f1f3f4;
}

.search-category:last-child {
    border-bottom: none;
}

.search-category-header {
    padding: 0.75rem 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.search-category-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
    display: flex;
    align-items: center;
}

.search-category-count {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.search-category-items {
    max-height: 200px;
    overflow-y: auto;
}

/* Search Result Items */
.search-result-item {
    display: block;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f8f9fa;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    cursor: pointer;
    outline: none;
}

.search-result-item:hover {
    background: #f8f9fa;
    text-decoration: none;
    color: inherit;
}

.search-result-item:focus {
    background: #e3f2fd;
    box-shadow: inset 3px 0 0 #0d6efd;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-icon {
    width: 40px;
    height: 40px;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.search-result-icon i {
    font-size: 1.1rem;
    color: #6c757d;
}

.search-result-content {
    flex: 1;
    min-width: 0;
}

.search-result-title {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
    line-height: 1.3;
}

.search-result-subtitle {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    line-height: 1.3;
}

.search-result-category {
    font-size: 0.7rem;
    color: #adb5bd;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

/* Search States */
.search-no-results {
    padding: 2rem;
    text-align: center;
    color: #6c757d;
}

.search-no-results i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.search-loading {
    padding: 1rem;
    text-align: center;
    color: #6c757d;
}

.search-error {
    padding: 1rem;
    text-align: center;
    color: #dc3545;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
    margin: 0.5rem;
}

/* Highlight search terms */
.search-highlight {
    background: #fff3cd;
    padding: 0.1rem 0.2rem;
    border-radius: 3px;
    font-weight: 600;
}

/* Search Tips */
#search-tips {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* Clear Search Button */
#clear-search {
    width: 24px;
    height: 24px;
    padding: 0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}

/* Search Filters Button */
#search-filters-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .header-search {
        max-width: none;
        margin: 0 1rem;
    }
    
    .search-results-dropdown {
        left: -1rem;
        right: -1rem;
    }
    
    .search-result-item {
        padding: 1rem;
    }
    
    .search-result-icon {
        width: 35px;
        height: 35px;
    }
}

/* Animation */
.search-results-dropdown {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .search-results-dropdown {
        background: #2d3748;
        border-color: #4a5568;
    }
    
    .search-results-header,
    .search-results-footer {
        background: #1a202c;
        border-color: #4a5568;
    }
    
    .search-category-header {
        background: #1a202c;
        border-color: #4a5568;
    }
    
    .search-result-item:hover {
        background: #4a5568;
    }
    
    .search-result-item:focus {
        background: #2b6cb0;
    }
    
    .search-result-title {
        color: #e2e8f0;
    }
    
    .search-result-subtitle {
        color: #a0aec0;
    }
    
    .search-result-category {
        color: #718096;
    }
}
</style>
