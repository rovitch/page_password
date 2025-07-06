const path = require('path');

module.exports = {
    mode: 'production',
    entry: './Resources/Private/Typescript/index.ts',
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                use: 'ts-loader',
                exclude: /node_modules/,
            },
        ],
    },
    resolve: {
        extensions: ['.tsx', '.ts', '.js'],
    },
    output: {
        filename: 'bundle.js',
        path: path.resolve(__dirname, 'Resources/Public/assets/js/'),
        library: {
            name: 'pagePassword',
            type: 'var',
        }
    },
};