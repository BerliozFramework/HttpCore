const webpack = require('webpack');
const path = require('path');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const ManifestPlugin = require('webpack-manifest-plugin');
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');
const CleanWebpackPlugin = require("clean-webpack-plugin");

// Create multiple instances
const extractCSS = new ExtractTextPlugin('css/[name].css');

const config = {
  entry: {
    'debug': path.resolve(__dirname, 'resources/Public/src/debug.js'),
    'debug-toolbar': path.resolve(__dirname, 'resources/Public/src/debug-toolbar.js'),
    'debug-caller': path.resolve(__dirname, 'resources/Public/src/debug-caller.js')
  },
  output: {
    path: path.resolve(__dirname, 'resources/Public/dist'),
    filename: 'js/[name].js'
  },
  devtool: 'source-map',
  module: {
    rules: [
      {
        test: /\.css$/,
        use: extractCSS.extract([
                                  {
                                    loader: 'css-loader',
                                    options: {
                                      minimize: true,
                                      sourceMap: true
                                    }
                                  },
                                  {
                                    loader: 'postcss-loader',
                                    options: {
                                      minimize: true,
                                      sourceMap: true
                                    }
                                  }
                                ])
      },
      {
        test: /\.scss$/,
        use: extractCSS.extract([
                                  {
                                    loader: 'css-loader',
                                    options: {
                                      minimize: true,
                                      sourceMap: true
                                    }
                                  },
                                  {
                                    loader: 'sass-loader',
                                    options: {
                                      minimize: true,
                                      sourceMap: true
                                    }
                                  }
                                ])
      },
      {
        test: /\.js$/,
        exclude: /(node_modules|bower_components)/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env']
          }
        }
      },
      {
        test: /\.(ttf|eot|svg|otf|woff(2)?)(\?v=[0-9]\.[0-9]\.[0-9])?$/,
        use: {
          loader: 'url-loader',
          options: {outputPath: '/fonts/'}
        }
      },
      {
        test: /\.(png|gif|jpe?g)?$/,
        use: {
          loader: 'file-loader',
          options: {outputPath: 'images/'}
        }
      }
    ]
  },
  plugins: [
    new CleanWebpackPlugin(['resources/Public/dist']),
    new webpack.ProvidePlugin({
                                $: 'jquery',
                                jQuery: 'jquery',
                                'window.jQuery': 'jquery',
                                Popper: ['popper.js', 'default']
                              }),
    extractCSS,
    new ManifestPlugin(),
    new UglifyJSPlugin({test: /\.js($|\?)/i})
  ]
};

module.exports = config;