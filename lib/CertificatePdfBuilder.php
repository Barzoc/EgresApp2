<?php

use DateTime;
use TCPDF;
use Throwable;

class CertificatePdfBuilder extends TCPDF
{
    private array $backgroundPalette = [
        'fill' => [255, 255, 255],
        'frame' => [9, 36, 131],
    ];

    public function __construct()
    {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->SetCreator('EgresApp2');
        $this->SetAuthor('EgresApp2');
        $this->SetTitle('Certificado de Título');
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetMargins(18, 18, 18);
        $this->SetAutoPageBreak(true, 18);
    }

    public function build(array $data, array $options = []): string
    {
        $this->AddPage();

        $palette = $options['palette'] ?? $this->backgroundPalette;

        $this->drawBackground($palette);
        $this->renderHeader($palette);
        $this->renderBody($data, $palette, $options);

        return $this->Output('', 'S');
    }

    private function drawBackground(array $palette): void
    {
        $pageWidth = $this->getPageWidth();
        $pageHeight = $this->getPageHeight();

        if (!empty($palette['fill'])) {
            [$r, $g, $b] = $palette['fill'];
            $this->SetFillColor($r, $g, $b);
            $this->Rect(0, 0, $pageWidth, $pageHeight, 'F');
        }

        if (!empty($palette['frame'])) {
            [$r, $g, $b] = $palette['frame'];
            $this->SetDrawColor($r, $g, $b);
            $this->SetLineWidth(2.2);
            $margin = 10;
            $this->Rect($margin, $margin, $pageWidth - ($margin * 2), $pageHeight - ($margin * 2));
        }

        $this->SetTextColor(0, 0, 0);
    }

    private function renderHeader(array $palette): void
    {
        [$r, $g, $b] = $palette['frame'] ?? [9, 36, 131];
        $this->SetTextColor($r, $g, $b);
        $this->SetY(30);

        $logoPath = $this->resolveLogoPath();
        if ($logoPath && is_file($logoPath)) {
            $this->Image($logoPath, ($this->getPageWidth() / 2) - 12, 30, 24, 24, '', '', '', false, 300);
            $this->SetY(58);
        }

        $this->SetFont('helvetica', 'B', 18);
        $this->Cell(0, 10, 'LICEO BICENTENARIO DOMINGO SANTA MARÍA', 0, 1, 'C');
        $this->SetFont('helvetica', 'I', 11);
        $this->Cell(0, 8, '"Desde nuestro norte ser más y engrandecer a Chile"', 0, 1, 'C');
        $this->Ln(6);
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 10, 'CERTIFICADO DE TÍTULO', 0, 1, 'C');
        $this->SetTextColor(0, 0, 0);
    }

    private function renderBody(array $data, array $palette, array $options): void
    {
        $this->SetY(95);
        $this->SetFont('times', '', 13);

        $nombre = $this->valueOrPlaceholder(mb_strtoupper(trim($data['nombre'] ?? ''), 'UTF-8'));
        $rut = $this->valueOrPlaceholder($this->formatRut($data['rut'] ?? ''));
        $titulo = $this->valueOrPlaceholder(mb_strtoupper(trim($data['titulo'] ?? ''), 'UTF-8'));
        $fechaTitulo = $this->formatDateParts($data['fecha_titulo'] ?? $data['fechaTitulo'] ?? null);
        $numeroRegistro = $this->valueOrPlaceholder(trim($data['numero_registro'] ?? $data['numeroRegistro'] ?? ''));
        $fechaEmision = $this->valueOrPlaceholder($this->formatLongDate($data['fecha_emision'] ?? null));
        $rector = trim($options['rector'] ?? $data['rector'] ?? 'Rector(a) del establecimiento');

        $bodyLines = [
            'En conformidad con los reglamentos vigentes, el Liceo Bicentenario Domingo Santa María certifica que',
            sprintf('con fecha %s se le confirió a Don (ña) %s.', $fechaTitulo['formatted'], $nombre),
            sprintf('RUT %s, el título de %s.', $rut, $titulo),
            'De acuerdo a lo prescrito en el artículo N° 12 del Decreto Ley N° 2516/2007,',
            sprintf('el título queda registrado con el N° %s.', $numeroRegistro),
            sprintf('Se emite el presente certificado con fecha %s.', $fechaEmision),
        ];

        foreach ($bodyLines as $index => $line) {
            $this->MultiCell(0, 8, $line, 0, 'C', false, 1, 0, '', true, 0, false, true, 0, 'M');
            $this->Ln($index === 2 ? 3 : 2);
        }

        $this->Ln(20);
        $this->SetFont('helvetica', 'I', 9);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 5, 'Este documento se emite sin firma ni timbre.', 0, 1, 'C');
        $this->SetTextColor(0, 0, 0);
        $this->Ln(8);
        $this->SetFont('helvetica', '', 11);
        $this->Cell(0, 5, '______________________________', 0, 1, 'C');
        $this->Cell(0, 6, mb_strtoupper($rector, 'UTF-8'), 0, 1, 'C');
        $this->SetFont('helvetica', 'I', 10);
        $this->Cell(0, 5, 'RECTOR(A)', 0, 1, 'C');
    }

    private function formatDateParts(?string $value): array
    {
        $placeholder = [
            'day' => '____',
            'month' => '________',
            'year' => '____',
            'formatted' => '____________________',
        ];

        if (!$value) {
            return $placeholder;
        }

        try {
            $date = new DateTime($value);
        } catch (Throwable $e) {
            return $placeholder;
        }

        $months = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $monthName = mb_strtoupper($months[(int) $date->format('n') - 1], 'UTF-8');
        $day = str_pad($date->format('d'), 2, '0', STR_PAD_LEFT);
        $year = $date->format('Y');

        return [
            'day' => $day,
            'month' => $monthName,
            'year' => $year,
            'formatted' => sprintf('%s de %s de %s', $day, $monthName, $year),
        ];
    }

    private function formatLongDate(?string $value): string
    {
        try {
            $date = $value ? new DateTime($value) : new DateTime();
        } catch (Throwable $e) {
            $date = new DateTime();
        }

        $months = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $month = $months[(int) $date->format('n') - 1];
        return sprintf('%d de %s de %s', (int) $date->format('j'), mb_strtoupper($month, 'UTF-8'), $date->format('Y'));
    }

    private function valueOrPlaceholder(string $value, string $placeholder = '____________________'): string
    {
        $trimmed = trim($value);
        return $trimmed !== '' ? $trimmed : $placeholder;
    }

    private function resolveLogoPath(): ?string
    {
        $candidates = [
            __DIR__ . '/../assets/img/imagenes/logo liceo.png',
            __DIR__ . '/../assets/img/logo.png',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function formatRut(string $rut): string
    {
        $clean = preg_replace('/[^0-9kK]/', '', $rut);
        if (strlen($clean) < 2) {
            return $rut;
        }
        $dv = strtoupper(substr($clean, -1));
        $digits = strrev(substr($clean, 0, -1));
        $formatted = [];
        for ($i = 0; $i < strlen($digits); $i++) {
            if ($i > 0 && $i % 3 === 0) {
                $formatted[] = '.';
            }
            $formatted[] = $digits[$i];
        }
        return strrev(implode('', $formatted)) . '-' . $dv;
    }
}
