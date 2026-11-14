<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CashSession;
use App\Models\Dates;
use App\Models\Money;
use App\Models\Product;
use App\Models\Repository;
use App\Models\Sale;
use App\Models\User;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpForbiddenException;
use Slim\Views\PhpRenderer;

final class CashController
{
  private const PAYMENT_METHODS = [
    "efectivo" => "Efectivo",
    "transferencia" => "Transferencia",
    "tarjeta" => "Tarjeta",
  ];
  private const MAX_LINES = 100;
  private const MAX_QUANTITY = 9999;
  private const PAGE_SIZE = 20;

  public function __construct(
    private readonly PhpRenderer $renderer,
    private readonly User $users,
    private readonly Repository $repositories,
    private readonly RepositoryController $repositoryController,
    private readonly CashSession $cashSessions,
    private readonly Sale $sales,
    private readonly Product $products,
  ) {}

  /** GET /caja → según cuántos repositorios tenga el usuario */
  public function index(Request $request, Response $response): Response
  {
    $userId = (int) $_SESSION["user_id"];
    $repositories = $this->repositories->listForUser($userId);

    if ($repositories === []) {
      flash("Primero crea un repositorio para usar la caja.");

      return redirect($response, "/dashboard");
    }

    if (count($repositories) === 1) {
      return redirect($response, "/repositorios/" . $repositories[0]["id"] . "/caja");
    }

    $user = $this->users->findById($userId);

    if ($user === null) {
      unset($_SESSION["user_id"]);

      return redirect($response, "/login");
    }

    return view(
      $this->renderer,
      $response,
      "caja/elegir.php",
      [
        "title" => "Caja registradora | Suppli",
        "userName" => (string) $user["name"],
        "activeNav" => "caja",
        "repositories" => $repositories,
      ],
      "layouts/app.php",
    );
  }

  /** GET /repositorios/{id}/caja → "Abrir caja" o pantalla de venta */
  public function show(Request $request, Response $response, array $args): Response
  {
    $repository = $this->repositoryController->memberOrFail($request, $args);
    $repositoryId = (int) $repository["id"];

    // Panel de confirmación de la última venta (se muestra una sola vez)
    $receipt = null;
    $doneId = $_SESSION["sale_done"][$repositoryId] ?? null;
    unset($_SESSION["sale_done"][$repositoryId]);

    if (is_int($doneId)) {
      $receipt = $this->sales->findForReceipt($doneId, $repositoryId);
    }

    return $this->renderRegister($response, $repository, [], [], [], $receipt);
  }

  /** POST /repositorios/{id}/caja/abrir (cualquier miembro) */
  public function open(Request $request, Response $response, array $args): Response
  {
    $repository = $this->repositoryController->memberOrFail($request, $args);
    $repositoryId = (int) $repository["id"];

    $data = (array) $request->getParsedBody();
    $input = trim((string) ($data["opening_amount"] ?? ""));
    $cents = Money::parseCents($input, true);

    if ($cents === null) {
      return $this->renderRegister(
        $response->withStatus(422),
        $repository,
        ["Ingresa un monto de apertura de 0 o más (hasta 99999999,99), con coma o punto y sin separador de miles."],
        ["opening_amount" => $input],
      );
    }

    $result = $this->cashSessions->open($repositoryId, (int) $_SESSION["user_id"], $cents);

    flash(
      $result === "already_open"
        ? "Ya había un turno abierto. Puedes seguir vendiendo."
        : "Caja abierta.",
    );

    return redirect($response, "/repositorios/" . $repositoryId . "/caja");
  }

  /** GET /repositorios/{id}/caja/productos?q=… → JSON del buscador (cualquier miembro) */
  public function searchProducts(Request $request, Response $response, array $args): Response
  {
    $repository = $this->repositoryController->memberOrFail($request, $args);

    $raw = $request->getQueryParams()["q"] ?? "";
    $q = mb_substr(trim(is_string($raw) ? $raw : ""), 0, 50);
    $minLength = ctype_digit($q) ? 1 : 2;

    $results = [];

    if ($q !== "" && mb_strlen($q) >= $minLength) {
      foreach ($this->products->search((int) $repository["id"], $q) as $product) {
        $results[] = [
          "id" => $product["id"],
          "code" => $product["code"],
          "name" => $product["name"],
          "price_cents" => $product["price_cents"],
          "price_text" => Money::formatCents($product["price_cents"]),
          "stock" => $product["stock"],
          "gondola" => $product["gondola_name"],
        ];
      }
    }

    $response->getBody()->write(
      json_encode(["results" => $results], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
    );

    return $response
      ->withHeader("Content-Type", "application/json; charset=utf-8")
      ->withHeader("Cache-Control", "no-store");
  }

  /** POST /repositorios/{id}/caja/ventas → confirma la venta (cualquier miembro) */
  public function sell(Request $request, Response $response, array $args): Response
  {
    // El repositorio sale SIEMPRE de la URL (con membresía verificada), nunca del formulario
    $repository = $this->repositoryController->memberOrFail($request, $args);
    $repositoryId = (int) $repository["id"];
    $userId = (int) $_SESSION["user_id"];

    $data = (array) $request->getParsedBody();

    // Protección contra doble envío: el token es de un solo uso
    $tokenState = $this->consumeToken($repositoryId, (string) ($data["sale_token"] ?? ""));

    if ($tokenState === "used") {
      flash("Esa venta ya se había procesado. No se duplicó: revisa el historial.");

      return redirect($response, "/repositorios/" . $repositoryId . "/caja");
    }

    $errors = [];

    if ($tokenState === "unknown") {
      $errors[] = "La pantalla de la caja caducó. Revisa la venta y vuelve a confirmar.";
    }

    $parsed = $this->parseCart($data["items"] ?? null);
    $errors = [...$errors, ...$parsed["errors"]];

    // Descuento
    $discountType = (string) ($data["discount_type"] ?? "none");
    $discountInput = trim((string) ($data["discount_value"] ?? ""));
    $discountParam = 0;

    if ($discountType === "percent") {
      $discountParam = Money::parsePercent($discountInput) ?? -1;

      if ($discountParam < 0) {
        $discountParam = 0;
        $errors[] = "El porcentaje de descuento debe estar entre 0 y 100, con hasta 2 decimales.";
      }
    } elseif ($discountType === "amount") {
      $amount = Money::parseCents($discountInput, true);

      if ($amount === null) {
        $errors[] = "El descuento debe ser un monto de 0 o más, con hasta 2 decimales y sin separador de miles.";
      } else {
        $discountParam = $amount;
      }
    } elseif ($discountType !== "none") {
      $discountType = "none";
      $errors[] = "Elige un tipo de descuento válido.";
    }

    // Método de pago y monto recibido
    $method = (string) ($data["payment_method"] ?? "");
    $receivedInput = trim((string) ($data["amount_received"] ?? ""));
    $received = null;

    if (!isset(self::PAYMENT_METHODS[$method])) {
      $errors[] = "Elige un método de pago.";
      $method = "";
    } elseif ($method === "efectivo") {
      $received = Money::parseCents($receivedInput, true);

      if ($received === null) {
        $errors[] = "Ingresa el monto recibido (0 o más, con coma o punto y sin separador de miles).";
      }
    }

    if ($errors === []) {
      $result = $this->sales->create(
        $repositoryId,
        $userId,
        $parsed["lines"],
        $discountType,
        $discountParam,
        $method,
        $received,
      );

      if ($result["status"] === "ok") {
        $_SESSION["sale_done"][$repositoryId] = $result["sale_id"];

        return redirect($response, "/repositorios/" . $repositoryId . "/caja");
      }

      $errors = $result["errors"];
    }

    // Error: misma pantalla (422) con el carrito reconstruido con datos frescos de la base
    return $this->renderRegister(
      $response->withStatus(422),
      $repository,
      $errors,
      [
        "discount_type" => $discountType,
        "discount_value" => $discountInput,
        "payment_method" => $method,
        "amount_received" => $receivedInput,
      ],
      $this->cartForClient($repositoryId, $parsed["lines"]),
    );
  }

  /** GET /repositorios/{id}/caja/cierre → resumen del turno y formulario de cierre */
  public function closeForm(Request $request, Response $response, array $args): Response
  {
    $repository = $this->repositoryController->memberOrFail($request, $args);
    $repositoryId = (int) $repository["id"];

    // Resultado del cierre que se acaba de hacer (se muestra una sola vez)
    $doneId = $_SESSION["close_done"][$repositoryId] ?? null;
    unset($_SESSION["close_done"][$repositoryId]);

    if (is_int($doneId)) {
      $closed = $this->cashSessions->findClosed($doneId, $repositoryId);

      if ($closed !== null) {
        return $this->renderCaja(
          $response,
          $repository,
          null,
          "caja/cierre.php",
          "cierre",
          "Caja cerrada",
          ["closed" => $closed, "summary" => null, "expectedCents" => 0, "errors" => [], "old" => []],
        );
      }
    }

    $session = $this->cashSessions->findOpen($repositoryId);

    if ($session === null) {
      flash("No hay un turno abierto.");

      return redirect($response, "/repositorios/" . $repositoryId . "/caja");
    }

    $this->assertCanClose($request, $repository, $session);

    return $this->renderClose($response, $repository, $session);
  }

  /** POST /repositorios/{id}/caja/cerrar (quien abrió el turno o un admin) */
  public function close(Request $request, Response $response, array $args): Response
  {
    $repository = $this->repositoryController->memberOrFail($request, $args);
    $repositoryId = (int) $repository["id"];

    $session = $this->cashSessions->findOpen($repositoryId);

    if ($session === null) {
      flash("No hay un turno abierto.");

      return redirect($response, "/repositorios/" . $repositoryId . "/caja");
    }

    $this->assertCanClose($request, $repository, $session);

    $data = (array) $request->getParsedBody();
    $countedInput = trim((string) ($data["counted_cash"] ?? ""));
    $note = trim((string) ($data["close_note"] ?? ""));
    $postedSession = filter_var($data["session_id"] ?? null, FILTER_VALIDATE_INT, [
      "options" => ["min_range" => 1],
    ]);

    $counted = Money::parseCents($countedInput, true);
    $errors = [];

    if ($postedSession !== (int) $session["id"]) {
      $errors[] = "El turno cambió mientras tenías esta pantalla abierta. Revisa el resumen y vuelve a intentar.";
    }

    if ($counted === null) {
      $errors[] = "Ingresa el efectivo contado (0 o más, con coma o punto y sin separador de miles).";
    }

    if (mb_strlen($note) > 255) {
      $errors[] = "La nota no puede superar los 255 caracteres.";
    }

    if ($errors !== [] || $counted === null) {
      return $this->renderClose(
        $response->withStatus(422),
        $repository,
        $session,
        $errors,
        ["counted_cash" => $countedInput, "close_note" => $note],
      );
    }

    $result = $this->cashSessions->close(
      $repositoryId,
      (int) $session["id"],
      (int) $_SESSION["user_id"],
      $counted,
      $note === "" ? null : $note,
    );

    if ($result["status"] !== "closed") {
      flash("Ese turno ya estaba cerrado.");

      return redirect($response, "/repositorios/" . $repositoryId . "/caja");
    }

    $_SESSION["close_done"][$repositoryId] = (int) $session["id"];
    flash("Turno cerrado.");

    return redirect($response, "/repositorios/" . $repositoryId . "/caja/cierre");
  }

  /** GET /repositorios/{id}/caja/ventas → historial (admin: todo; miembro: solo lo suyo) */
  public function history(Request $request, Response $response, array $args): Response
  {
    $repository = $this->repositoryController->memberOrFail($request, $args);
    $repositoryId = (int) $repository["id"];
    $isAdmin = $repository["role"] === "admin";
    $query = $request->getQueryParams();

    $desdeInput = self::queryText($query, "desde");
    $hastaInput = self::queryText($query, "hasta");
    $desde = null;
    $hasta = null;
    $errors = [];

    if ($desdeInput !== "") {
      $desde = Dates::parseDate($desdeInput);

      if ($desde === null) {
        $errors[] = "La fecha «desde» no es válida.";
      }
    }

    if ($hastaInput !== "") {
      $hasta = Dates::parseDate($hastaInput);

      if ($hasta === null) {
        $errors[] = "La fecha «hasta» no es válida.";
      }
    }

    if ($errors === [] && $desde !== null && $hasta !== null && $desde > $hasta) {
      $errors[] = "La fecha «desde» no puede ser posterior a «hasta».";
    }

    // Con errores no se aplica ningún filtro
    if ($errors !== []) {
      $desde = null;
      $hasta = null;
    }

    $from = $desde !== null ? $desde . " 00:00:00" : null;
    $toExclusive = $hasta !== null
      ? (new DateTimeImmutable($hasta))->modify("+1 day")->format("Y-m-d") . " 00:00:00"
      : null;
    $onlyUserId = $isAdmin ? null : (int) $_SESSION["user_id"];

    $total = $this->sales->countForHistory($repositoryId, $onlyUserId, $from, $toExclusive);
    $pages = max(1, (int) ceil($total / self::PAGE_SIZE));
    $page = filter_var($query["pagina"] ?? 1, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    $page = $page === false ? 1 : min($page, $pages);

    $sales = $this->sales->listForHistory(
      $repositoryId,
      $onlyUserId,
      $from,
      $toExclusive,
      self::PAGE_SIZE,
      ($page - 1) * self::PAGE_SIZE,
    );

    // Los ítems de toda la página, en una sola consulta
    $itemsBySale = $this->sales->itemsForSales(array_column($sales, "id"));

    foreach ($sales as $index => $sale) {
      $sales[$index]["items"] = $itemsBySale[$sale["id"]] ?? [];
    }

    $filterQuery = [];

    if ($desde !== null) {
      $filterQuery["desde"] = $desde;
    }

    if ($hasta !== null) {
      $filterQuery["hasta"] = $hasta;
    }

    return $this->renderCaja(
      $errors === [] ? $response : $response->withStatus(422),
      $repository,
      $this->cashSessions->findOpen($repositoryId),
      "caja/historial.php",
      "historial",
      "Historial de ventas",
      [
        "isAdmin" => $isAdmin,
        "sales" => $sales,
        "total" => $total,
        "page" => $page,
        "pages" => $pages,
        "errors" => $errors,
        "old" => ["desde" => $desdeInput, "hasta" => $hastaInput],
        "filterQuery" => $filterQuery,
        "sessions" => $isAdmin ? $this->cashSessions->listRecent($repositoryId, 10) : [],
      ],
    );
  }

  // ---------------------------------------------------------------------------
  // Render
  // ---------------------------------------------------------------------------

  /**
   * Pantalla principal de la caja: "Abrir caja" si no hay turno, o la pantalla de venta.
   *
   * @param array{id: int, name: string, color: string, role: string} $repository
   * @param list<string>         $errors
   * @param array<string, mixed> $old
   * @param list<array<string, mixed>> $cart
   * @param array<string, mixed>|null  $receipt
   */
  private function renderRegister(
    Response $response,
    array $repository,
    array $errors = [],
    array $old = [],
    array $cart = [],
    ?array $receipt = null,
  ): Response {
    $repositoryId = (int) $repository["id"];
    $session = $this->cashSessions->findOpen($repositoryId);

    if ($session === null) {
      return $this->renderCaja(
        $response,
        $repository,
        null,
        "caja/abrir.php",
        "venta",
        "Abrir caja",
        ["errors" => $errors, "old" => $old],
      );
    }

    return $this->renderCaja(
      $response,
      $repository,
      $session,
      "caja/pos.php",
      "venta",
      "Caja",
      [
        "errors" => $errors,
        "old" => $old,
        "cart" => $cart,
        "receipt" => $receipt,
        "saleToken" => $this->issueToken($repositoryId),
      ],
    );
  }

  /**
   * @param array{id: int, name: string, color: string, role: string} $repository
   * @param array<string, mixed> $session
   * @param list<string>         $errors
   * @param array<string, mixed> $old
   */
  private function renderClose(
    Response $response,
    array $repository,
    array $session,
    array $errors = [],
    array $old = [],
  ): Response {
    $summary = $this->cashSessions->summary((int) $session["id"]);
    $expected = (int) $session["opening_cents"] + $summary["by_method"]["efectivo"]["total_cents"];

    return $this->renderCaja(
      $response,
      $repository,
      $session,
      "caja/cierre.php",
      "cierre",
      "Cerrar caja",
      [
        "closed" => null,
        "summary" => $summary,
        "expectedCents" => $expected,
        "errors" => $errors,
        "old" => $old,
      ],
    );
  }

  /**
   * Renderiza una vista dentro de layouts/caja.php → layouts/base.php (sin columna lateral).
   *
   * @param array{id: int, name: string, color: string, role: string} $repository
   * @param array<string, mixed>|null $session  Turno abierto (para el encabezado) o null.
   * @param array<string, mixed>      $data
   */
  private function renderCaja(
    Response $response,
    array $repository,
    ?array $session,
    string $template,
    string $nav,
    string $title,
    array $data = [],
  ): Response {
    $user = $this->users->findById((int) $_SESSION["user_id"]);

    if ($user === null) {
      unset($_SESSION["user_id"]);

      return redirect($response, "/login");
    }

    $canClose = $session !== null
      && ($repository["role"] === "admin" || (int) $session["opened_by"] === (int) $user["id"]);

    return view(
      $this->renderer,
      $response,
      $template,
      [
        "title" => $title . " | Suppli",
        "userName" => (string) $user["name"],
        "repository" => $repository,
        "session" => $session,
        "canClose" => $canClose,
        "cajaNav" => $nav,
        ...$data,
      ],
      "layouts/caja.php",
    );
  }

  // ---------------------------------------------------------------------------
  // Ayudas privadas
  // ---------------------------------------------------------------------------

  /**
   * Cerrar el turno: quien lo abrió o un admin; cualquier otro miembro → 403.
   *
   * @param array{id: int, name: string, color: string, role: string} $repository
   * @param array<string, mixed> $session
   */
  private function assertCanClose(Request $request, array $repository, array $session): void
  {
    $isOpener = (int) $session["opened_by"] === (int) $_SESSION["user_id"];

    if ($repository["role"] !== "admin" && !$isOpener) {
      throw new HttpForbiddenException($request);
    }
  }

  /**
   * Valida los ítems enviados (máx. 100 líneas, cantidad entera 1–9.999, sin
   * product_id repetido). Devuelve solo las líneas válidas y los errores.
   *
   * @return array{lines: list<array{product_id: int, quantity: int}>, errors: list<string>}
   */
  private function parseCart(mixed $raw): array
  {
    if (!is_array($raw) || $raw === []) {
      return ["lines" => [], "errors" => ["Agrega al menos un producto a la venta."]];
    }

    $errors = [];

    if (count($raw) > self::MAX_LINES) {
      $errors[] = "Una venta puede tener como máximo " . self::MAX_LINES . " productos distintos.";
      $raw = array_slice($raw, 0, self::MAX_LINES);
    }

    $lines = [];
    $seen = [];

    foreach ($raw as $row) {
      if (!is_array($row)) {
        $errors[] = "Hay una línea inválida en la venta.";
        continue;
      }

      $productId = filter_var($row["product_id"] ?? null, FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1],
      ]);

      if ($productId === false) {
        $errors[] = "Hay una línea inválida en la venta.";
        continue;
      }

      if (isset($seen[$productId])) {
        $errors[] = "Hay productos repetidos en la venta. Usa la cantidad en lugar de agregarlos dos veces.";
        continue;
      }

      $seen[$productId] = true;

      $quantityText = is_scalar($row["quantity"] ?? null) ? trim((string) $row["quantity"]) : "";
      $quantity = preg_match('/^\d{1,4}$/', $quantityText) === 1 ? (int) $quantityText : 0;

      if ($quantity < 1 || $quantity > self::MAX_QUANTITY) {
        $errors[] = "La cantidad de cada producto debe ser un entero entre 1 y 9.999.";
        continue;
      }

      $lines[] = ["product_id" => $productId, "quantity" => $quantity];
    }

    return ["lines" => $lines, "errors" => array_values(array_unique($errors))];
  }

  /**
   * Carrito para el cliente (JS) con datos FRESCOS de la base: precio y stock actuales.
   * Los productos que ya no existen en el repositorio se omiten.
   *
   * @param list<array{product_id: int, quantity: int}> $lines
   * @return list<array<string, mixed>>
   */
  private function cartForClient(int $repositoryId, array $lines): array
  {
    if ($lines === []) {
      return [];
    }

    $byId = [];

    foreach ($this->products->findForCart($repositoryId, array_column($lines, "product_id")) as $product) {
      $byId[$product["id"]] = $product;
    }

    $cart = [];

    foreach ($lines as $line) {
      $product = $byId[$line["product_id"]] ?? null;

      if ($product === null) {
        continue;
      }

      $cart[] = [
        "id" => $product["id"],
        "code" => $product["code"],
        "name" => $product["name"],
        "price_cents" => $product["price_cents"],
        "stock" => $product["stock"],
        "quantity" => $line["quantity"],
      ];
    }

    return $cart;
  }

  /**
   * Emite un token de un solo uso para la pantalla de venta (guardado en sesión, por repositorio).
   * Se guardan hasta 10 abiertos para que dos pestañas del mismo negocio no se pisen.
   */
  private function issueToken(int $repositoryId): string
  {
    $bucket = $_SESSION["sale_tokens"][$repositoryId] ?? ["open" => [], "used" => []];
    $token = bin2hex(random_bytes(16));

    $bucket["open"][] = $token;
    $bucket["open"] = array_slice($bucket["open"], -10);

    $_SESSION["sale_tokens"][$repositoryId] = $bucket;

    return $token;
  }

  /**
   * Consume un token: 'ok' (válido, ahora gastado), 'used' (ya se había usado: envío repetido)
   * o 'unknown' (no existe: pantalla caducada). La sesión de PHP serializa los pedidos
   * simultáneos, así que dos envíos a la vez no pueden consumir el mismo token.
   *
   * @return 'ok'|'used'|'unknown'
   */
  private function consumeToken(int $repositoryId, string $token): string
  {
    $bucket = $_SESSION["sale_tokens"][$repositoryId] ?? ["open" => [], "used" => []];

    foreach ($bucket["open"] as $index => $open) {
      if (hash_equals((string) $open, $token)) {
        unset($bucket["open"][$index]);
        $bucket["open"] = array_values($bucket["open"]);
        $bucket["used"][] = $token;
        $bucket["used"] = array_slice($bucket["used"], -20);
        $_SESSION["sale_tokens"][$repositoryId] = $bucket;

        return "ok";
      }
    }

    foreach ($bucket["used"] as $used) {
      if (hash_equals((string) $used, $token)) {
        return "used";
      }
    }

    return "unknown";
  }

  /**
   * @param array<string, mixed> $query
   */
  private static function queryText(array $query, string $key): string
  {
    $value = $query[$key] ?? "";

    return is_string($value) ? trim($value) : "";
  }
}