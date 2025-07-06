import { scopedPreflightStyles, isolateInsideOfContainer } from 'tailwindcss-scoped-preflight';

/** @type {import('tailwindcss').Config} */
function withOpacity(variableName) {
  return ({ opacityValue }) => {
    if (opacityValue !== undefined) {
      return `rgba(var(${variableName}), ${opacityValue})`
    }
    return `rgb(var(${variableName}))`
  }
}

/** @type {import('tailwindcss').Config} */
module.exports = {
  plugins: [
    // ...
    scopedPreflightStyles({
      isolationStrategy: isolateInsideOfContainer('#page-password-form', {}),
    }),
  ],
  content: [
    "./Resources/Private/Templates/**/*.html",
    "./Resources/Private/Partials/**/*.html"
  ],
  safelist: [
    'dark:bg-zinc-950'
  ],
  theme: {
    extend: {
      maxWidth: {
        'md': '30rem',
      },
      textColor: {
        custom: {
          light: withOpacity("--color-light"),
          a11y: {
            light: withOpacity("--color-a11y-light"),
            dark: withOpacity("--color-a11y-dark"),
          }
        },
      },
      backgroundColor: {
        custom: {
          light: withOpacity("--color-light"),
          dark: withOpacity("--color-dark"),
          a11y: {
            light: withOpacity("--color-a11y-light"),
            dark: withOpacity("--color-a11y-dark"),
          }
        },
      },
      ringColor: {
        custom: {
          light: withOpacity("--color-light"),
          dark: withOpacity("--color-dark"),
        },
      },
      borderColor: {
        custom: {
          light: withOpacity("--color-light"),
          dark: withOpacity("--color-dark"),
          a11y: {
            light: withOpacity("--color-a11y-light"),
            dark: withOpacity("--color-a11y-dark"),
          }
        },
      },
      fill: {
        custom: {
          light: withOpacity("--color-light"),
          dark: withOpacity("--color-dark"),
          a11y: {
            light: withOpacity("--color-a11y-light"),
            dark: withOpacity("--color-a11y-dark"),
          }
        },
      },
    },
  }
}

