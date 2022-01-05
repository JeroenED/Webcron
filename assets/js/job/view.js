import 'bootstrap';
import Utils from "../utils";

document.addEventListener("readystatechange", event => {
    if(event.target.readyState === 'complete') {
        initTags();
    }
});

function initTags() {
    var tags = JSON.parse(localStorage.getItem('tags')) ?? new Object();
    var collected = Object.keys(tags);
    document.querySelectorAll('.job-name').forEach(elem => {
        let matches = elem.textContent.matchAll(/\[([A-Za-z0-9 \-]+)\]/g)
        for (const tag of matches) {
            if (typeof tag != 'undefined') {
                if(collected.indexOf(tag[1]) == -1) {
                    let color = '#'+tag[1].hashCode().toString(16).substr(1,6)// ; (0x1000000+Math.random()*0xffffff).toString(16).substr(1,6)
                    collected.push(tag[1]);
                    tags[tag[1]] = color;
                }
                let tagcolor =  tags[tag[1]];
                let newelem = document.createElement('span')
                newelem.classList.add('tag');
                newelem.innerHTML = tag[1];
                newelem.style.backgroundColor = tagcolor;
                newelem.style.color = Utils.lightOrDark(tagcolor) == 'dark' ? '#ffffff' : '#000000';
                elem.innerHTML = elem.innerHTML.replace(tag[0], newelem.outerHTML);
            }
        }
    })
    localStorage.setItem('tags', JSON.stringify(tags));
}