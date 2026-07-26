<?php

use PHPUnit\Framework\TestCase;

/**
 * Test di Speed Up - Optimize CSS Delivery.
 *
 * Tutta la logica sta in style_loader_tag(): riscrive il tag di ogni foglio di
 * stile, estraendone l'attributo media dall'HTML che WordPress ha gia'
 * prodotto. Un errore qui non rompe una funzione secondaria, lascia il sito
 * senza CSS.
 */
class OptimizeCssDeliveryTest extends TestCase {

    /** Tag come lo produce WordPress prima del filtro. */
    private const WP_TAG = "<link rel='stylesheet' id='tema-css' href='https://example.test/style.css' type='text/css' media='all' />\n";

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['speedup_calls'] = array('add_action' => array(), 'add_filter' => array());
        $GLOBALS['speedup_exclude_handle'] = false;
    }

    private function filter($html, $handle = 'tema-css', $href = 'https://example.test/style.css') {
        return SpeedUp_OptimizeCSSDelivery::get_instance()->style_loader_tag($html, $handle, $href);
    }

    // -----------------------------------------------------------------------
    // Struttura
    // -----------------------------------------------------------------------

    public function test_get_instance_restituisce_sempre_la_stessa_istanza() {
        $first  = SpeedUp_OptimizeCSSDelivery::get_instance();
        $second = SpeedUp_OptimizeCSSDelivery::get_instance();

        $this->assertInstanceOf('SpeedUp_OptimizeCSSDelivery', $first);
        $this->assertSame($first, $second);
    }

    public function test_si_aggancia_a_style_loader_tag_e_wp_head() {
        $filters = array_column($GLOBALS['speedup_registered_at_load']['add_filter'], 0);
        $actions = array_column($GLOBALS['speedup_registered_at_load']['add_action'], 0);

        $this->assertContains('style_loader_tag', $filters);
        $this->assertContains('wp_head', $actions);
    }

    /**
     * In bacheca il plugin non deve toccare i fogli di stile: registra soltanto
     * l'avviso di deprecazione.
     */
    public function test_in_area_amministrativa_registra_solo_l_avviso() {
        $original_instance = SpeedUp_OptimizeCSSDelivery::$instance;
        $original_is_admin = $GLOBALS['speedup_is_admin'];

        try {
            SpeedUp_OptimizeCSSDelivery::$instance = null;
            $GLOBALS['speedup_is_admin'] = true;

            SpeedUp_OptimizeCSSDelivery::get_instance();

            $this->assertSame(
                array(),
                $GLOBALS['speedup_calls']['add_filter'],
                'Nessun filtro sui fogli di stile in bacheca.'
            );
            $this->assertSame(
                array('admin_notices'),
                array_column($GLOBALS['speedup_calls']['add_action'], 0),
                'Solo l\'avviso di deprecazione, e nient\'altro.'
            );
        } finally {
            SpeedUp_OptimizeCSSDelivery::$instance = $original_instance;
            $GLOBALS['speedup_is_admin'] = $original_is_admin;
        }
    }

    // -----------------------------------------------------------------------
    // Riscrittura del tag
    // -----------------------------------------------------------------------

    public function test_trasforma_lo_stylesheet_in_preload() {
        $out = $this->filter(self::WP_TAG);

        $this->assertStringContainsString('rel="preload"', $out);
        $this->assertStringContainsString('as="style"', $out);
        $this->assertStringContainsString('href="https://example.test/style.css"', $out);
    }

    public function test_include_un_fallback_noscript() {
        $out = $this->filter(self::WP_TAG);

        $this->assertStringContainsString('<noscript>', $out);
        $this->assertStringContainsString('</noscript>', $out);
        // Senza JavaScript il foglio di stile deve caricarsi comunque.
        $this->assertStringContainsString('rel="stylesheet"', $out);
    }

    public function test_conserva_l_attributo_media() {
        $tag = "<link rel='stylesheet' id='stampa-css' href='https://example.test/p.css' type='text/css' media='print' />\n";

        $out = $this->filter($tag, 'stampa-css', 'https://example.test/p.css');

        $this->assertStringContainsString('media="print"', $out);
        $this->assertStringNotContainsString('media="all"', $out);
    }

    public function test_usa_all_quando_media_non_c_e() {
        $tag = "<link rel='stylesheet' id='x-css' href='https://example.test/x.css' type='text/css' />\n";

        $out = $this->filter($tag, 'x-css', 'https://example.test/x.css');

        $this->assertStringContainsString('media="all"', $out);
    }

    /**
     * La regex che estrae media non deve essere greedy: questo filtro gira con
     * priorita' PHP_INT_MAX, quindi altri filtri possono aver gia' aggiunto
     * attributi dopo media. Con "(.*)" verrebbero risucchiati nel valore.
     */
    public function test_non_risucchia_gli_attributi_che_seguono_media() {
        $tag = "<link rel='stylesheet' id='y-css' href='https://example.test/y.css' media='screen' data-extra='qualcosa' />\n";

        $out = $this->filter($tag, 'y-css', 'https://example.test/y.css');

        $this->assertStringContainsString('media="screen"', $out);
        $this->assertStringNotContainsString('data-extra', $out);
    }

    public function test_lascia_intatto_il_tag_se_l_handle_e_escluso() {
        $GLOBALS['speedup_exclude_handle'] = true;

        $out = $this->filter(self::WP_TAG);

        $this->assertSame(self::WP_TAG, $out);
    }

    /**
     * handle e href finiscono dentro attributi HTML: se un altro plugin li
     * altera, non devono poter chiudere l'attributo.
     */
    public function test_escapa_handle_e_href() {
        $out = $this->filter(
            self::WP_TAG,
            'cattivo" onload="alert(1)',
            'https://example.test/s.css" onerror="alert(2)'
        );

        $this->assertStringNotContainsString('onload="alert(1)"', $out);
        $this->assertStringNotContainsString('onerror="alert(2)"', $out);
    }

    // -----------------------------------------------------------------------
    // Script inline
    // -----------------------------------------------------------------------

    public function test_stampa_il_polyfill_loadcss() {
        $plugin = SpeedUp_OptimizeCSSDelivery::get_instance();

        ob_start();
        $plugin->print_inline_script();
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('<script', $out);
        $this->assertStringContainsString('loadCSS', $out);
        $this->assertStringContainsString('</script>', $out);
    }
    // -----------------------------------------------------------------------
    // Avviso di deprecazione
    // -----------------------------------------------------------------------

    private function noticeOutput() {
        ob_start();
        SpeedUp_OptimizeCSSDelivery::get_instance()->deprecation_notice();
        return (string) ob_get_clean();
    }

    public function test_l_avviso_dice_che_il_plugin_non_serve_piu() {
        $messaggio = SpeedUp_OptimizeCSSDelivery::deprecation_message();

        $this->assertStringContainsString('notice notice-warning', $messaggio);
        $this->assertStringContainsString('no longer recommended', $messaggio);
    }

    /**
     * Non richiudibile di proposito: un avviso che si puo' chiudere viene
     * dimenticato, e il punto e' che il plugin non ha piu' ragione di girare.
     */
    public function test_l_avviso_non_e_richiudibile() {
        $this->assertStringNotContainsString('is-dismissible', SpeedUp_OptimizeCSSDelivery::deprecation_message());
    }

    /**
     * @dataProvider schermateInCuiSiVede
     */
    public function test_si_vede_dove_e_azionabile($schermata) {
        $GLOBALS['speedup_screen_id'] = $schermata;
        $GLOBALS['speedup_can_manage'] = true;

        $this->assertNotSame('', $this->noticeOutput(), "Doveva comparire su: $schermata");
    }

    public function schermateInCuiSiVede() {
        return array(
            'bacheca'         => array('dashboard'),
            'bacheca di rete' => array('dashboard-network'),
            'plugin'          => array('plugins'),
            'plugin di rete'  => array('plugins-network'),
        );
    }

    /**
     * Un avviso non richiudibile su ogni schermata sarebbe fastidioso invece
     * che informativo.
     */
    public function test_non_si_vede_altrove() {
        $GLOBALS['speedup_screen_id'] = 'post';
        $GLOBALS['speedup_can_manage'] = true;

        $this->assertSame('', $this->noticeOutput());
    }

    public function test_non_si_vede_a_chi_non_puo_disattivare_plugin() {
        $GLOBALS['speedup_screen_id'] = 'plugins';
        $GLOBALS['speedup_can_manage'] = false;

        $this->assertSame('', $this->noticeOutput());
    }

    public function test_regge_l_assenza_di_una_schermata() {
        $GLOBALS['speedup_screen_id'] = null;
        $GLOBALS['speedup_can_manage'] = true;

        $this->assertSame('', $this->noticeOutput());
    }
}
