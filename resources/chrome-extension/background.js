// GrowthZone Extension Service Worker
chrome.runtime.onInstalled.addListener(() => {
    console.log("GrowthZone Extension installed.");
});

// Minimal background worker for now.
// Auth is handled in popup, and scanning is handled via script injection from popup.
