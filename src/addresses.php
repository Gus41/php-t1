<?php
require_once 'services/SessionsService.php';
require_once 'dao/AddressDAO.php';

$session = new SessionManager();
$user = $session->currentUser();
if (!$user || !$session->hasRole(['superuser'])) {
    header('Location: index.php');
    exit;
}

$addressDAO = new AddressDAO();
$addresses = $addressDAO->findAllWithOwners();

include 'partials/header.php';
?><main class="flex-grow flex justify-center px-6 py-12">
  <div style="width:100%;max-width:1100px">

    <h1 style="font-family:'DM Serif Display',serif;font-size:30px;font-weight:400;letter-spacing:-0.01em;margin:0 0 4px">Endereços.</h1>
    <p style="font-size:13px;font-weight:300;color:rgba(240,236,228,0.35);margin:0 0 2.5rem">Listagem completa de todos os endereços cadastrados.</p>

    <?php if (empty($addresses)): ?>
      <div style="border:1px solid rgba(240,236,228,0.08);border-radius:10px;padding:48px;text-align:center;font-size:13px;color:rgba(240,236,228,0.3);letter-spacing:0.04em">
        Nenhum endereço encontrado.
      </div>
    <?php else: ?>
      <div style="overflow-x:auto;border:1px solid rgba(240,236,228,0.08);border-radius:10px">
        <table style="width:100%;border-collapse:collapse;font-size:13px;color:#f0ece4">
          <thead>
            <tr style="border-bottom:1px solid rgba(240,236,228,0.08)">
              <?php foreach (['ID','Endereço','Proprietário','E-mail','Tipo'] as $col): ?>
                <th style="padding:12px 16px;text-align:left;font-size:10px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:rgba(240,236,228,0.28);white-space:nowrap">
                  <?= $col ?>
                </th>
              <?php endforeach ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($addresses as $i => $item):
              $addr = htmlspecialchars(trim(implode(', ', array_filter([
                $item['street'],
                $item['complement'] ?? '',
                $item['neighborhood'],
                $item['city'],
                $item['state'],
                $item['zip_code'],
              ]))));
              $owner = htmlspecialchars($item['user_name'] ?? $item['supplier_name'] ?? '—');
              $email = htmlspecialchars($item['user_email'] ?? $item['supplier_email'] ?? '—');
              $tipo  = $item['user_id'] ? 'Usuário' : ($item['supplier_id'] ? 'Fornecedor' : 'Sem vínculo');
              $rowBg = $i % 2 === 0 ? 'transparent' : 'rgba(240,236,228,0.02)';
            ?>
              <tr style="border-bottom:1px solid rgba(240,236,228,0.05);background:<?= $rowBg ?>;transition:background 0.15s"
                  onmouseover="this.style.background='rgba(240,236,228,0.05)'"
                  onmouseout="this.style.background='<?= $rowBg ?>'">

                <td style="padding:14px 16px;color:rgba(240,236,228,0.3);font-size:12px;font-weight:500;letter-spacing:0.04em;white-space:nowrap">
                  #<?= htmlspecialchars($item['id']) ?>
                </td>

                <td style="padding:14px 16px;color:rgba(240,236,228,0.75);max-width:320px">
                  <?= $addr ?>
                </td>

                <td style="padding:14px 16px;color:#f0ece4;font-weight:500;white-space:nowrap">
                  <?= $owner ?>
                </td>

                <td style="padding:14px 16px;color:rgba(240,236,228,0.45);white-space:nowrap">
                  <?= $email ?>
                </td>

                <td style="padding:14px 16px">
                  <?php
                    $badge = match($tipo) {
                      'Usuário'     => ['rgba(240,236,228,0.07)', 'rgba(240,236,228,0.5)',  'Usuário'],
                      'Fornecedor'  => ['rgba(29,158,117,0.1)',   'rgba(29,158,117,0.6)',   'Fornecedor'],
                      default       => ['rgba(240,236,228,0.04)', 'rgba(240,236,228,0.2)',  'Sem vínculo'],
                    };
                  ?>
                  <span style="background:<?= $badge[0] ?>;color:<?= $badge[1] ?>;border:1px solid <?= $badge[1] ?>;border-radius:6px;padding:3px 10px;font-size:11px;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;white-space:nowrap">
                    <?= $badge[2] ?>
                  </span>
                </td>

              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    <?php endif ?>

  </div>
</main>
 <?php include 'partials/footer.php';
