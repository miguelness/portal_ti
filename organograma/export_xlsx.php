<?php
// export_xlsx.php — Exporta lista de colaboradores para XLSX
require_once '../admin/check_access.php';

/* ========= Conexão ========= */
$base = __DIR__;
if (file_exists($base . '/config.php')) {
  require_once $base . '/config.php';
} else {
  die('Crie config.php no mesmo diretório com $conn.');
}

// Verifica se o PhpSpreadsheet está disponível
if (!file_exists('../vendor/autoload.php')) {
  die('Biblioteca PhpSpreadsheet não encontrada. Execute "composer require phpoffice/phpspreadsheet" na raiz do projeto.');
}

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Parâmetros de filtro (mesmos do index.php)
$selectedEmpresas = isset($_GET['empresas']) ? (array)$_GET['empresas'] : ['todos'];
$qTerm = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

// Monta cláusula para filtrar por empresa
$whereEmpresa = '';
if (!empty($selectedEmpresas)) {
  // Se tiver "todos" marcado, não filtra
  $todosMarcado = in_array('todos', array_map('strtolower', $selectedEmpresas));
  if (!$todosMarcado) {
    // Sanitiza nomes das empresas previstos
    $validas = [];
    foreach ($selectedEmpresas as $em) {
      $em = trim((string)$em);
      if (in_array($em, ['Barão','Toymania','Alfaness','Barao'])) {
        // normaliza acento
        if ($em === 'Barao') $em = 'Barão';
        $validas[] = $conn->real_escape_string($em);
      }
    }
    if (!empty($validas)) {
      $inList = "'" . implode("','", $validas) . "'";
      // Condição para incluir sempre Grupo Barão, considerando variações de acento
      $grupoBaraoCond = "(LOWER(empresa) LIKE '%grupo%' AND (LOWER(empresa) LIKE '%barão%' OR LOWER(empresa) LIKE '%barao%'))";
      $whereEmpresa = " AND (empresa IN ($inList) OR $grupoBaraoCond)";
    }
  }
}

// Monta cláusula de busca
$whereBusca = '';
if ($qTerm !== '') {
  $qEsc = $conn->real_escape_string($qTerm);
  $like = "%$qEsc%";
  $whereBusca = " AND (nome LIKE '$like' OR cargo LIKE '$like' OR departamento LIKE '$like' OR empresa LIKE '$like' OR email LIKE '$like' OR telefone LIKE '$like' OR ramal LIKE '$like')";
}

// Consulta SQL para obter os dados
$sql = "SELECT nome, cargo, departamento, empresa, ramal, telefone, email, teams
        FROM colaboradores
        WHERE COALESCE(ativo,1)=1" . $whereEmpresa . $whereBusca . "
        ORDER BY nome";

$result = $conn->query($sql);
if (!$result) {
  die('Erro ao buscar dados: ' . $conn->error);
}

// Criar uma nova planilha
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Colaboradores');

// Definir cabeçalhos
$headers = ['Nome', 'Cargo', 'Departamento', 'Empresa', 'Ramal', 'Telefone', 'E-mail', 'Teams'];
$sheet->fromArray($headers, NULL, 'A1');

// Estilo para cabeçalhos
$headerStyle = [
  'font' => [
    'bold' => true,
    'color' => ['rgb' => 'FFFFFF'],
  ],
  'fill' => [
    'fillType' => Fill::FILL_SOLID,
    'startColor' => ['rgb' => '0F172A'],
  ],
  'alignment' => [
    'horizontal' => Alignment::HORIZONTAL_CENTER,
    'vertical' => Alignment::VERTICAL_CENTER,
  ],
  'borders' => [
    'allBorders' => [
      'borderStyle' => Border::BORDER_THIN,
      'color' => ['rgb' => 'CBD5E1'],
    ],
  ],
];

$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

// Preencher dados
$row = 2;
while ($data = $result->fetch_assoc()) {
  $sheet->setCellValue('A' . $row, $data['nome']);
  $sheet->setCellValue('B' . $row, $data['cargo']);
  $sheet->setCellValue('C' . $row, $data['departamento']);
  $sheet->setCellValue('D' . $row, $data['empresa']);
  $sheet->setCellValue('E' . $row, $data['ramal']);
  $sheet->setCellValue('F' . $row, $data['telefone']);
  $sheet->setCellValue('G' . $row, $data['email']);
  $sheet->setCellValue('H' . $row, $data['teams']);
  $row++;
}

// Estilo para as células de dados
$dataStyle = [
  'borders' => [
    'allBorders' => [
      'borderStyle' => Border::BORDER_THIN,
      'color' => ['rgb' => 'CBD5E1'],
    ],
  ],
  'alignment' => [
    'vertical' => Alignment::VERTICAL_CENTER,
  ],
];

if ($row > 2) {
  $sheet->getStyle('A2:H' . ($row - 1))->applyFromArray($dataStyle);
}

// Auto-dimensionar colunas
foreach (range('A', 'H') as $col) {
  $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Definir cabeçalhos HTTP para download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="colaboradores_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

// Salvar arquivo
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;