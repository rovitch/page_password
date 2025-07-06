export const hexToRGB = (hex: string) => {
    let color = hex.replace(/#/g, "")

    const r = parseInt(color.substring(0, 2), 16)
    const g = parseInt(color.substring(2, 4), 16)
    const b = parseInt(color.substring(4, 6), 16)

    return {r, g, b}
}

export const getRGBColor = (hex: string, type: string) => {
    const {r, g, b} = hexToRGB(hex)
    return `--color-${type}: ${r}, ${g}, ${b};`
}

export const getAccessibleColor = (hex: string) => {
    const { r, g, b } = hexToRGB(hex)
    const yiq = (r * 299 + g * 587 + b * 114) / 1000
    return yiq >= 128 ? "#000000" : "#FFFFFF"
}