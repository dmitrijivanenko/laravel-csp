<?php

namespace Spatie\Csp\Tests;

use Illuminate\Support\Facades\Route;
use Spatie\Csp\AddCspHeaders;
use Spatie\Csp\Exceptions\InvalidCspProfile;
use Spatie\Csp\Profiles\Basic;
use Symfony\Component\HttpFoundation\HeaderBag;

class AddCspHeadersTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Route::get('test', function () {
            return 'ok';
        })->middleware(AddCspHeaders::class);
    }

    /** @test */
    public function the_default_configuration_will_only_set_report_only_headers()
    {
        $headers = $this->getResponseHeaders();

        $this->assertNotNull($headers->get('Content-Security-Policy-Report-Only'));
        $this->assertContains("default-src 'self';", $headers->get('Content-Security-Policy-Report-Only'));

        $this->assertNull($headers->get('Content-Security-Policy'));
    }

    /** @test */
    public function it_can_set_the_basic_csp_headers()
    {
        config([
            'csp.profile' => Basic::class,
            'csp.report_only_profile' => '',
        ]);

        $headers = $this->getResponseHeaders();

        $this->assertContains("default-src 'self';", $headers->get('Content-Security-Policy'));
    }

    /** @test */
    public function it_wont_set_any_headers_if_not_enabled_in_the_config()
    {
        config([
            'csp.profile' => Basic::class,
            'csp.report_only_profile' => '',
            'csp.enabled' => false,
        ]);

        $headers = $this->getResponseHeaders();

        $this->assertNull($headers->get('Content-Security-Policy'));
    }

    /** @test */
    public function a_report_uri_can_be_set_in_the_config()
    {
        config(['csp.report_uri' => 'https://report-uri.com']);

        $headers = $this->getResponseHeaders();

        $reportOnlyHeaderContent = $headers->get('Content-Security-Policy-Report-Only');

        $this->assertContains(
            'report-uri https://report-uri.com',
            $reportOnlyHeaderContent
        );

        $this->assertContains(
            'report-to {"url":"https:\/\/report-uri.com","group-name":"Basic","max-age":18144000};',
            $reportOnlyHeaderContent
        );
    }

    /** @test */
    public function using_an_invalid_profile_class_will_throw_an_exception()
    {
        $this->withoutExceptionHandling();

        $invalidProfileClassName = get_class(new class {});

        config(['csp.profile' => $invalidProfileClassName]);

        $this->expectException(InvalidCspProfile::class);

        $this->getResponseHeaders();
    }

    protected function getResponseHeaders(): HeaderBag
    {
        return $this
            ->get('test')
            ->assertSuccessful()
            ->headers;
    }
}