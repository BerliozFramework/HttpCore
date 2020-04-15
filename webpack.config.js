const path = require('path');
const AssetsPlugin = require('assets-webpack-plugin');
const {CleanWebpackPlugin} = require("clean-webpack-plugin");
const FriendlyErrorsWebpackPlugin = require('friendly-errors-webpack-plugin');
const ManifestPlugin = require('webpack-manifest-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const SimpleProgressWebpackPlugin = require('simple-progress-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const WebpackNotifierPlugin = require('webpack-notifier');

const purgeManifestFile = (name) => {
    return name.replace(/^/, '/')
        .replace(/\\/g, '/')
        .replace(/\/{2,}/g, '/')
        .replace(/(\?v=[0-9.]*)$/, '')
};

module.exports = (env, argv) => {
    const devMode = argv.mode !== 'production';

    return {
        devtool: devMode ? 'source-map' : false,
        mode: argv.mode || 'production',
        context: __dirname,
        entry: {
            'debug': './resources/Public/src/debug.js',
            'debug-toolbar': './resources/Public/src/debug-toolbar.js',
            'debug-caller': './resources/Public/src/debug-caller.js',
        },
        output: {
            path: path.resolve(__dirname, 'resources/Public/dist/'),
            filename: 'js/[name].[contenthash:8].js',
            publicPath: '/_console/dist/',
            pathinfo: false
        },
        module: {
            rules: [
                {
                    test: /\.jsx?$/,
                    use:
                        {
                            loader: 'babel-loader',
                            options: {
                                presets: ['@babel/preset-env'],
                                plugins: ['@babel/plugin-syntax-dynamic-import'],
                                sourceMap: devMode
                            }
                        }
                },
                {
                    test: /\.(c|s[c|a])ss$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        {
                            loader: "css-loader",
                            options: {sourceMap: devMode, importLoaders: 1}
                        },
                        {
                            loader: 'postcss-loader',
                            options: {sourceMap: devMode}
                        },
                        {
                            loader: 'resolve-url-loader',
                            options: {sourceMap: devMode}
                        },
                        {
                            loader: 'sass-loader',
                            options: {sourceMap: true}
                        },
                    ],
                },
                {
                    test: /\.(ttf|eot|otf|woff2?|svg)(\?v=[0-9.]*)?$/,
                    include: /font(s)?/,
                    use: {
                        loader: 'file-loader',
                        options: {
                            name: '[name].[hash:8].[ext]',
                            outputPath: 'fonts/'
                        }
                    }
                },
            ]
        },
        optimization: {
            minimize: !devMode,
            minimizer: [
                new TerserPlugin({
                    test: /\.js($|\?)/i,
                    sourceMap: devMode
                }),
                new OptimizeCSSAssetsPlugin({})
            ],
            splitChunks: {
                cacheGroups: {
                    vendor: {
                        test: /\.js($|\?)/i,
                        chunks: 'all',
                        minChunks: 2,
                        name: 'vendor',
                        enforce: true
                    }
                }
            },
        },
        performance: {
            hints: false
        },
        plugins: [
            new MiniCssExtractPlugin({
                filename: "css/[name].[hash:8].css",
                chunkFilename: "css/[id].[hash:8].css"
            }),
            new AssetsPlugin({
                entrypoints: true,
                publicPath: true,
                filename: 'entrypoints.json',
                path: 'resources/Public/dist'
            }),
            new ManifestPlugin({
                map: (file) => {
                    file.name = purgeManifestFile(file.name);
                    file.path = purgeManifestFile(file.path);
                    return file
                }
            }),
            new CleanWebpackPlugin({
                cleanStaleWebpackAssets: false
            }),
            new FriendlyErrorsWebpackPlugin(),
            new SimpleProgressWebpackPlugin({format: 'compact'}),
            new WebpackNotifierPlugin({alwaysNotify: true})
        ]
    }
};