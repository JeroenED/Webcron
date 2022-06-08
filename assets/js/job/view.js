import 'bootstrap';
import '/assets/scss/job/view.scss';
import Utils from "./Utils";

document.addEventListener("readystatechange", event => {
    if(event.target.readyState === 'complete') {
        Utils.initTags();
    }
});