let Utils = {};

Utils.initTags = () =>  {
    document.querySelectorAll('.tag').forEach(elem => {
        let backcolor = elem.dataset.backgroundColor;
        let frontcolor = elem.dataset.color;
        elem.style.backgroundColor = backcolor;
        elem.style.color = frontcolor;
    })
}

Utils.timepickerOptions = {
    localization:{
        locale: 'nl',
        format: 'dd/MM/yyyy HH:mm:ss'
    },
    display: {
        icons: {
            time: 'icon-clock-o',
            date: 'icon-calendar',
            up: 'icon-arrow-up',
            down: 'icon-arrow-down',
            previous: 'icon-chevron-left',
            next: 'icon-chevron-right',
            today: 'icon-calendar-check-o',
            clear: 'icon-delete',
            close: 'icon-x',
        },
        components: {
            seconds: true,
            useTwentyfourHour: true
        }
    },
}

export default Utils;