let Utils = {};

Utils.initTags = () =>  {
    document.querySelectorAll('.tag').forEach(elem => {
        let backcolor = elem.dataset.backgroundColor;
        let frontcolor = elem.dataset.color;
        elem.style.backgroundColor = backcolor;
        elem.style.color = frontcolor;
    })
}

export default Utils;