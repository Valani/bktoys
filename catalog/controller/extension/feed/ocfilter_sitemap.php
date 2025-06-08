<?php
class ControllerExtensionFeedOCFilterSitemap extends Controller {
  private $category_link = [];
  private $url_suffix = null;

  private $langmark_settings = false;

  public function index() {
    if (!$this->registry->has('ocfilter') || !$this->ocfilter->config('sitemap_status')) {
      return;
    }

    $language_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "language WHERE status = '1'");

    $multi_language = ($language_query->num_rows > 1);

    // Langmark compatibility
    $this->langmark_settings = $this->config->get('asc_langmark_' . $this->config->get('config_store_id'));

    if ($this->langmark_settings && (empty($this->langmark_settings['multi']) || count($this->langmark_settings['multi']) < 2)) {
      $this->langmark_settings = false;
    }

    if ($multi_language) {
      if ($this->ocfilter->opencart->version >= 30) {
        $seo_url_query = $this->db->query("SELECT `query` FROM " . DB_PREFIX . "seo_url WHERE `query` LIKE 'category_id=%' LIMIT 1");
      } else {
        $seo_url_query = $this->db->query("SELECT `query` FROM " . DB_PREFIX . "url_alias WHERE `query` LIKE 'category_id=%' LIMIT 1");
      }

      if ($seo_url_query->num_rows) {
        $compare_links = [];

        foreach ($language_query->rows as $language) {
          $this->setLanguage($language['language_id'], $language['code']);

          $compare_links[] = $this->url->link('product/category', 'path=' . str_replace('category_id=', '', $seo_url_query->row['query']));
        }

        $multi_language = (count(array_unique($compare_links)) > 1);
      } else {
        $multi_language = false;
      }
    }

    // URL's
    $urlset = '';

    if ($this->config->get('langdir_dir')) {
      // Customized by LangDir in URL
      $uri = ltrim($this->request->server['REQUEST_URI'], '/');

      $locale = stristr($uri, '/', true);

      if (false === ($language_id = array_search($locale, $this->config->get('langdir_dir')))) {
        $language_id = $this->config->get('config_language_id');
      }

      if ($language_id) {
        foreach ($language_query->rows as $language) {
          if ($language_id == $language['language_id']) {
            $this->setLanguage($language_id, $language['code']);

            $urlset .= $this->getLanguageOutput($language_id);

            break;
          }
        }
      }
    } else if (!$multi_language) {
      if (!$this->config->get('config_language_id')) {
        $first_language = array_shift($language_query->rows);

        $this->config->set('config_language_id', $first_language['language_id']);
      }

      $urlset .= $this->getLanguageOutput(0);
    } else {
      foreach ($language_query->rows as $language) {
        $this->setLanguage($language['language_id'], $language['code']);

        $urlset .= $this->getLanguageOutput($language['language_id']);
      }
    }

    // Output
    if ($urlset) {
      $output  = '<?xml version="1.0" encoding="UTF-8"?>';
      $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . $urlset . '</urlset>';

      $this->ocfilter->opencart->responseXML($output);
    }
  }

  protected function getLanguageOutput($language_id) {
    $output = '';

    $pages = $this->model_extension_module_ocfilter->getPages([ 'filter_sitemap' => true, ]);

    foreach ($pages as $page) {
      $cache_key = $language_id . '.' . $page['category_id'];

      if (isset($this->category_link[$cache_key])) {
        $link = $this->category_link[$cache_key];
      } else {
        $link = $this->url->link('product/category', 'path=' . $page['path'], 'SSL');

        if (is_null($this->url_suffix)) {
          $this->url_suffix = $this->ocfilter->seo->getUrlSuffix($link);
        }

        $link = rtrim($link, '/');

        if (false !== utf8_strpos($link, '.html')) {
          $link = utf8_substr($link, 0, utf8_strpos($link, '.html'));
        }

        $this->category_link[$cache_key] = $link;
      }

      if ($page['keyword'] && false === strpos($link, 'index.php?route=')) {
        $link .= '/' . $page['keyword'] . $this->url_suffix;
      } else {
        $link .= (false === utf8_strpos($link, '?') ? '?' : '&amp;') . 'ocfilter_page_id=' . $page['page_id'];
      }

      $output .= '<url>';
      $output .= '<loc>' . $link . '</loc>';
      $output .= '<changefreq>weekly</changefreq>';
      $output .= '<priority>0.6</priority>';
      $output .= '</url>';
    }

    return $output;
  }

  private function setLanguage($language_id, $code) {
    $this->config->set('config_language', $code);
    $this->config->set('config_language_id', $language_id);
    $this->session->data['language'] = $code;

    // OCD Multilang
    if ($this->config->get('ocd_multilang_language')) {
      $this->config->set('ocd_multilang_language', $language_id);

      $language_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE `language_id` = '" . (int)$language_id . "'");

      if (isset($language_query->row['url'])) {
        $this->session->data['language_url'] = $language_query->row['url'];
      }
    }

    // Langmark
    if ($this->langmark_settings) {
      foreach ($this->langmark_settings['multi'] as $name => $settings_multi) {
        if (isset($settings_multi['name']) && $language_id == $settings_multi['language_id']) {
          $this->registry->set('langmark_multi', $settings_multi);

          break;
        }
      }
    }

    // Langmark old
    if ($this->langmark_settings && is_file(DIR_APPLICATION . 'controller/record/langmark.php')) {
      if ($this->ocfilter->opencart->version < 22) {
        $this->load->controller('record/langmark/switchLanguage', [ $this->langmark_settings, $language_id, $code ]);
      } else {
        $this->load->controller('record/langmark/switchLanguage', $this->langmark_settings, $language_id, $code);
      }
    }
  }
}