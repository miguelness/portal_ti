<?php
/**
 * Contador Export Excel - Exportação de estatísticas de cliques para Excel
 */

// Verificar autenticação (usando o mesmo sistema do portal)
// require_once '../admin/check_access.php';

// Verificar se a biblioteca PhpSpreadsheet está disponível
if (!file_exists('../vendor/autoload.php')) {
    die("A biblioteca PhpSpreadsheet não está disponível. Execute 'composer require phpoffice/phpspreadsheet' no diretório raiz.");
}

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Incluir configuração do sistema
require_once '../config.php';

// Conectar ao banco de dados
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Verificar se a tabela existe
$tableExists = $conn->query("SHOW TABLES LIKE 'contador_cliques'")->num_rows > 0;
if (!$tableExists) {
    die("A tabela contador_cliques ainda não existe. Nenhum clique foi registrado.");
}

// Criar uma nova planilha
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Estatísticas de Cliques');

// Definir cabeçalhos
$sheet->setCellValue('A1', 'Data/Hora');
$sheet->setCellValue('B1', 'IP');
$sheet->setCellValue('C1', 'Navegador');
$sheet->setCellValue('D1', 'Origem');
$sheet->setCellValue('E1', 'Referência');

// Estilizar cabeçalhos
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '0EA5E9'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
];

$sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

// Obter dados do banco de dados
$sql = "SELECT * FROM contador_cliques ORDER BY data_hora DESC";
$result = $conn->query($sql);

// Preencher dados
$row = 2;
if ($result && $result->num_rows > 0) {
    while ($data = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $data['data_hora']);
        $sheet->setCellValue('B' . $row, $data['ip']);
        $sheet->setCellValue('C' . $row, $data['user_agent']);
        $sheet->setCellValue('D' . $row, $data['origem']);
        $sheet->setCellValue('E' . $row, $data['referer']);
        $row++;
    }
}

// Estilizar células de dados
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC'],
        ],
    ],
];
$sheet->getStyle('A2:E' . ($row - 1))->applyFromArray($dataStyle);

// Ajustar largura das colunas automaticamente
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Adicionar filtros
$sheet->setAutoFilter('A1:E1');

// Congelar a primeira linha
$sheet->freezePane('A2');

// Definir o nome do arquivo
$filename = 'Estatisticas_Cliques_' . date('Y-m-d_H-i-s') . '.xlsx';

// Configurar cabeçalhos para download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Salvar o arquivo
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;