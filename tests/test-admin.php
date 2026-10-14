<?php

class AdminTest extends \WP_UnitTestCase
{
    /**
     * @var \PressbooksCasSso\Admin
     */
    protected $admin;

    /**
     *
     */
    public function set_up()
    {
        parent::set_up();
        $this->admin = new \PressbooksCasSso\Admin;
    }

    public function test_addMenu()
    {
        $this->admin->addMenu();
        $this->assertTrue(true); // Did not crash
    }

    public function test_init_returns_singleton()
    {
        $instance1 = \PressbooksCasSso\Admin::init();
        $instance2 = \PressbooksCasSso\Admin::init();
        $this->assertInstanceOf('\PressbooksCasSso\Admin', $instance1);
        $this->assertSame($instance1, $instance2);
    }

    public function test_hooks_defers_textdomain_load_to_init()
    {
        // load_plugin_textdomain() (WP 6.7+) registers a custom path on the global
        // textdomain registry rather than loading translations immediately, so that's
        // the reliable signal to check here. The plugin's own bootstrap (plugins_loaded,
        // in tests/bootstrap.php) already ran once for real before any test's hook
        // snapshot was taken, so reset that leftover state first to properly isolate
        // this test's own call.
        global $wp_textdomain_registry;
        $property = new \ReflectionProperty($wp_textdomain_registry, 'custom_paths');
        $property->setAccessible(true);
        $paths = $property->getValue($wp_textdomain_registry);
        unset($paths['pressbooks-cas-sso']);
        $property->setValue($wp_textdomain_registry, $paths);

        \PressbooksCasSso\Admin::hooks($this->admin);
        $this->assertArrayNotHasKey(
            'pressbooks-cas-sso',
            $property->getValue($wp_textdomain_registry),
            'load_plugin_textdomain() must not run before init (WP 6.7+ _doing_it_wrong)'
        );

        do_action('init');
        $this->assertArrayHasKey(
            'pressbooks-cas-sso',
            $property->getValue($wp_textdomain_registry),
            'load_plugin_textdomain() should run once init fires'
        );
    }

    public function test_printMenu()
    {
        ob_start();
        $this->admin->printMenu();
        $buffer = ob_get_clean();
        $this->assertStringContainsString('</form>', $buffer);
    }

    public function test_options()
    {

        $options = $this->admin->getOptions();

        $this->assertEquals($options['server_version'], 'CAS_VERSION_2_0');
        $this->assertEquals($options['server_hostname'], '');
        $this->assertEquals($options['server_port'], 443);
        $this->assertEquals($options['server_path'], '/');
        $this->assertEquals($options['provision'], 'refuse');
        $this->assertEquals($options['email_domain'], '');
        $this->assertEquals($options['button_text'], '');
        $this->assertEquals($options['bypass'], 0);
        $this->assertEquals($options['forced_redirection'], 0);

        $_REQUEST['_wpnonce'] = wp_create_nonce('pb-cas-sso');
        $_POST = [
            'server_version' => 'CAS_VERSION_3_0',
            'server_hostname' => 'cas.server.edu',
            'server_port' => '999',
            'server_path' => '/foo/bar',
            'provision' => 'create',
            'email_domain' => '@pressbooks.test',
            'button_text' => 'Connect via<br>Some SSO Provider<script src="http://evil-script.com/script.js"></script>',
            'bypass' => '1',
            'forced_redirection' => '1',
        ];
        $this->admin->saveOptions();
        $options = $this->admin->getOptions();

        $this->assertEquals($options['server_version'], 'CAS_VERSION_3_0');
        $this->assertEquals($options['server_hostname'], 'cas.server.edu');
        $this->assertEquals($options['server_port'], 999);
        $this->assertEquals($options['server_path'], '/foo/bar/');
        $this->assertEquals($options['provision'], 'create');
        $this->assertEquals($options['email_domain'], 'pressbooks.test');
        $this->assertEquals($options['button_text'], 'Connect via<br>Some SSO Provider');
        $this->assertEquals($options['bypass'], 1);
        $this->assertEquals($options['forced_redirection'], 1);
    }

}
