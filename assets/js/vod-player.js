document.addEventListener('DOMContentLoaded', () => {
  const players = document.querySelectorAll('.wp-vod-player');

  players.forEach(player => {
      const source = player.querySelector('source');

      if (source && source.getAttribute('type') === 'application/x-mpegURL') {
          if (Hls.isSupported()) {
              const hls = new Hls();
              hls.loadSource(source.src);
              hls.attachMedia(player);
              hls.on(Hls.Events.MANIFEST_PARSED, () => {
                  new Plyr(player);
              });
          } else if (player.canPlayType('application/vnd.apple.mpegurl')) {
              new Plyr(player);
              player.src = source.src;
          }
      } else {
          new Plyr(player);
      }
  });
});
