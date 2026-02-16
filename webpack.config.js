const webpack = require('webpack');
const path = require('path');
const AssetsPlugin = require('assets-webpack-plugin');
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const {WebpackManifestPlugin} = require('webpack-manifest-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const WebpackNotifierPlugin = require('webpack-notifier');

module.exports = (env, argv) => {
    const devMode = argv.mode !== 'production';
    const config = {
        devtool: devMode ? 'eval-source-map' : false,
        mode: argv.mode || 'production',
        context: __dirname,
        entry: {
            'debug': './resources/Public/src/debug.js',
            'debug-toolbar': './resources/Public/src/debug-toolbar.js',
            'debug-caller': './resources/Public/src/debug-caller.js',
        },
        output: {
            path: path.resolve(__dirname, 'resources/Public/dist'),
            filename: 'js/[name].[contenthash:8].js',
            publicPath: '/_console/dist/',
            pathinfo: false,
            clean: {
                keep: /entrypoints\.json/
            },
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
                        {
                            loader: MiniCssExtractPlugin.loader,
                            options: {
                                publicPath: '../',
                            },
                        },
                        {
                            loader: "css-loader",
                            options: {sourceMap: devMode, importLoaders: 1}
                        },
                        {
                            loader: 'postcss-loader',
                            options: {
                                sourceMap: devMode,
                                postcssOptions: {
                                    plugins: [
                                        'postcss-preset-env',
                                        'autoprefixer',
                                    ]
                                }
                            }
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
                    type: 'asset/resource',
                    generator: {
                        filename: 'fonts/[name].[hash:8][ext][query]'
                    }
                }
            ]
        },
        optimization: {
            minimize: !devMode,
            minimizer: [
                `...`,
                new CssMinimizerPlugin()
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
            new webpack.ProgressPlugin(),
            new MiniCssExtractPlugin({
                filename: "css/[name].[fullhash:8].css",
                chunkFilename: "css/[id].[fullhash:8].css"
            }),
            new AssetsPlugin({
                entrypoints: true,
                filename: 'entrypoints.json',
                useCompilerPath: true,
            }),
            new WebpackManifestPlugin({}),
            new WebpackNotifierPlugin({alwaysNotify: true}),
        ]
    };

    if (devMode) {
        config.plugins.push(new webpack.SourceMapDevToolPlugin({}));
    }

    return config;
};
