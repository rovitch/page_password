import {getRGBColor, getAccessibleColor} from './modules';

export function addCustomStyles(lightColorHex: string, darkColorHex: string) {
    const lightColor = getRGBColor(lightColorHex, "light")
    const darkColor = getRGBColor(darkColorHex, "dark")
    const lightA11yColor = getRGBColor(getAccessibleColor(lightColorHex), "a11y-light")
    const darkA11yColor = getRGBColor(getAccessibleColor(darkColorHex), "a11y-dark")
    const styles = `:root { ${lightColor} ${darkColor} ${lightA11yColor} ${darkA11yColor} }`
    const styleSheet = document.createElement("style")
    styleSheet.textContent = styles
    document.head.appendChild(styleSheet)
}

export function bindSubmitForm(form: HTMLFormElement) {
    form.addEventListener('submit', (event) => {
        form.getElementsByTagName('button').item(0).disabled = true
        form.getElementsByTagName('svg').item(0).classList.remove('hidden')
    })
}