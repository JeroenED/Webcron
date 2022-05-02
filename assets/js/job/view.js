import 'bootstrap';
import '/assets/scss/job/view.scss';

document.addEventListener("readystatechange", event => {
    if(event.target.readyState === 'complete') {
        initTags();
    }
});

function initTags() {
    document.querySelectorAll('.tag').forEach(elem => {
        let backcolor = elem.dataset.backgroundColor;
        let frontcolor = elem.dataset.color;
        elem.style.backgroundColor = backcolor;
        elem.style.color = frontcolor;
    })
}