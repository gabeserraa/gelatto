import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'

// The service worker updates itself in the background (registerType:
// autoUpdate), but a tab left open across a deploy stays on the old SW
// until it's told to take over. Reload once that happens so nobody gets
// stuck on stale cached chunks after we ship. A page's FIRST ever visit
// also fires controllerchange (the SW claiming an unclaimed page via
// clients.claim() in src/sw.js) — that's not an update, so only reload
// when this page was already under a controller when it loaded.
if ('serviceWorker' in navigator) {
  const hadController = Boolean(navigator.serviceWorker.controller)
  let reloaded = false
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    if (!hadController || reloaded) return
    reloaded = true
    window.location.reload()
  })
}

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
