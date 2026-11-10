// Reddit posts {type:"resize.embed", data:<px>} after measuring the post.
window.addEventListener('message', function (event) {
    if (event.origin !== 'https://embed.reddit.com' &&
        event.origin !== 'https://www.redditmedia.com')
        return;
    var data = event.data;
    if (typeof data === 'string') {
        try { data = JSON.parse(data); }
        catch (e) { return; }
    }
    if (!data || data.type !== 'resize.embed')
        return;
    var height = parseInt(data.data, 10);
    if (!(height >= 1 && height <= 4000))
        return;
    var frames = document.getElementsByTagName('iframe');
    for (var i = 0; i < frames.length; i++) {
        if (frames[i].contentWindow === event.source) {
            frames[i].height = height;
            frames[i].style.height = height + 'px';
            return;
        }
    }
});
