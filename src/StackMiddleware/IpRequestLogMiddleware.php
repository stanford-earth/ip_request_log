<?php

namespace Drupal\ip_request_log\StackMiddleware;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class IpRequestLogMiddleware implements HttpKernelInterface {

  public function __construct(
    protected HttpKernelInterface $httpKernel,
    protected LoggerChannelInterface $logger,
    protected Settings $settings,
  ) {}

  public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = TRUE): Response {
    $start = microtime(TRUE);
    $response = $this->httpKernel->handle($request, $type, $catch);

    $ips = $this->settings->get('ip_request_log_ips', []);
    if ($type === self::MAIN_REQUEST && in_array($request->getClientIp(), $ips, TRUE)) {
      $this->logger->info('@method @uri → @status (@ms ms) | UA: @ua | Referer: @ref', [
        '@method' => $request->getMethod(),
        '@uri' => $request->getRequestUri(),
        '@status' => $response->getStatusCode(),
        '@ms' => round((microtime(TRUE) - $start) * 1000),
        '@ua' => $request->headers->get('User-Agent', '-'),
        '@ref' => $request->headers->get('Referer', '-'),
      ]);
    }

    return $response;
  }

}
