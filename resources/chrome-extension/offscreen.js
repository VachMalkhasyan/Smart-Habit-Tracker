chrome.runtime.onMessage.addListener((msg) => {
    if (msg.target === 'offscreen' && msg.type === 'play_audio') {
        playAudio(msg.data.base64);
    }
});

function playAudio(base64) {
    const audio = document.getElementById('audio');
    audio.src = base64;
    audio.play()
        .then(() => console.log('Audio playing'))
        .catch((e) => console.error('Error playing audio', e));
}
