const colorSchemeQueryList = window.matchMedia('(prefers-color-scheme: dark)');

const setColorScheme = e => {
    let newColorScheme = e.matches ? "dark" : "light";
    document.querySelector('body').dataset.bsTheme = newColorScheme;
}

setColorScheme(colorSchemeQueryList);
colorSchemeQueryList.addEventListener('change', setColorScheme);