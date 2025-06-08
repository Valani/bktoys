<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

 class ModelOCTemplatesWidgetsOctMinify extends Model {
    public function octMinifyCss($data) {
        $isMinifyEnabled = !empty($this->config->get('theme_oct_remarket_data')['minify']);
        $styles = $this->document->getOctStyles();

        if ($isMinifyEnabled) {
            $minifyDir = dirname(DIR_APPLICATION) . '/catalog/view/theme/oct_remarket/stylesheet/';
            if (!is_dir($minifyDir)) {
                @mkdir($minifyDir, 0755, true);
            }

            $serverUrl = $this->request->server['HTTPS'] ? $this->config->get('config_ssl') : $this->config->get('config_url');
            $cacheFilename = md5(json_encode($styles)) . '.css';
            $cacheFile = $minifyDir . $cacheFilename;
            $externalStyles = [];

            if (!file_exists($cacheFile)) {
                $fileResource = fopen($cacheFile, 'a+');
                foreach ($styles as $href => $style) {
                    $parsedUrl = parse_url($href);
                    if (!isset($parsedUrl['host'])) {
                        $localPath = dirname(DIR_APPLICATION) . '/' . ltrim($href, '/');
                        if (file_exists($localPath)) {
                            $content = file_get_contents($localPath);
                            $content = $this->minifyCSS($content);
                            fwrite($fileResource, $content . PHP_EOL);
                        }
                    } else {
                        $externalStyles[$href] = [
                            'href'  => $href,
                            'rel'   => $style['rel'],
                            'media' => $style['media']
                        ];
                    }
                }
                fclose($fileResource);
            } else {
                foreach ($styles as $href => $style) {
                    $parsedUrl = parse_url($href);
                    if (isset($parsedUrl['host'])) {
                        $externalStyles[$href] = [
                            'href'  => $href,
                            'rel'   => $style['rel'],
                            'media' => $style['media']
                        ];
                    }
                }
            }

            if (file_exists($cacheFile)) {
                $styles = [];
                $styles[] = [
                    'href'  => $serverUrl . 'catalog/view/theme/oct_remarket/stylesheet/' . $cacheFilename . '?' . date('YmdHis', filemtime($cacheFile)),
                    'rel'   => 'stylesheet',
                    'media' => 'screen'
                ];
                $styles = array_merge($styles, $externalStyles);
            }
        }

        return $styles;
    }

    public function octMinifyJs() {
        $isMinifyEnabled = !empty($this->config->get('theme_oct_remarket_data')['minify']);
        $scripts = $this->document->getOctScripts();

        if ($isMinifyEnabled) {
            $minifyDir = dirname(DIR_APPLICATION) . '/catalog/view/theme/oct_remarket/stylesheet/';
            if (!is_dir($minifyDir)) {
                @mkdir($minifyDir, 0755, true);
            }

            $serverUrl = $this->request->server['HTTPS'] ? $this->config->get('config_ssl') : $this->config->get('config_url');
            $cacheFilename = md5(json_encode($scripts)) . '.js';
            $cacheFile = $minifyDir . $cacheFilename;
            $externalScripts = [];

            if (!file_exists($cacheFile)) {
                $fileResource = fopen($cacheFile, 'a+');
                foreach ($scripts as $href) {
                    $parsedUrl = parse_url($href);
                    if (!isset($parsedUrl['host'])) {
                        $localPath = dirname(DIR_APPLICATION) . '/' . ltrim($href, '/');
                        if (file_exists($localPath)) {
                            $content = file_get_contents($localPath);
                            $content = $this->minifyJS($content);
                            fwrite($fileResource, $content . PHP_EOL);
                        }
                    } else {
                        $externalScripts[$href] = $href;
                    }
                }
                fclose($fileResource);
            } else {
                foreach ($scripts as $href) {
                    $parsedUrl = parse_url($href);
                    if (isset($parsedUrl['host'])) {
                        $externalScripts[$href] = $href;
                    }
                }
            }

            if (file_exists($cacheFile)) {
                $scripts = [];
                $scripts[] = $serverUrl . 'catalog/view/theme/oct_remarket/stylesheet/' . $cacheFilename . '?' . date('YmdHis', filemtime($cacheFile));
                $scripts = array_merge($scripts, $externalScripts);
            }
        }

        return $scripts;
    }

    protected function minifyCSS($content) {
        $content = preg_replace('!/\*.*?\*/!s', '', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/\s*([{}|:;,])\s*/', '$1', $content);
        return trim($content);
    }

    protected function minifyJS($content) {
        $content = preg_replace('/\n\s*\n/', "\n", $content);
        $content = preg_replace('/^"use strict";/', '', $content);
        return trim($content);
    }
}