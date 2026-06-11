<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; padding: 30px; }
  h1 { font-size: 20px; color: #1565C0; margin-bottom: 4px; }
  .subtitle { color: #666; font-size: 11px; margin-bottom: 20px; }
  .stats { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  .stat-box { background: #f0f4ff; padding: 12px 16px; width: 25%; }
  .stat-box .label { font-size: 10px; color: #666; margin-bottom: 4px; }
  .stat-box .value { font-size: 16px; font-weight: bold; color: #1565C0; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  th { background: #1565C0; color: white; padding: 8px 10px; text-align: left; font-size: 11px; }
  td { padding: 7px 10px; border-bottom: 1px solid #e8e8e8; font-size: 11px; }
  tr:nth-child(even) td { background: #f8f9ff; }
  .text-right { text-align: right; }
  .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>
  <h1>Moliyaviy hisobot</h1>
  <div class="subtitle">Hisobot sanasi: {{ now()->format('d.m.Y H:i') }}</div>

  <table class="stats">
    <tr>
      <td class="stat-box">
        <div class="label">Jami daromad</div>
        <div class="value">{{ number_format($stats['total_revenue'] ?? 0, 0, '.', ' ') }} so'm</div>
      </td>
      <td class="stat-box">
        <div class="label">Jami bemorlar</div>
        <div class="value">{{ $stats['total_patients'] ?? 0 }}</div>
      </td>
      <td class="stat-box">
        <div class="label">Kunlik o'rtacha</div>
        <div class="value">{{ number_format($stats['avg_per_day'] ?? 0, 0, '.', ' ') }} so'm</div>
      </td>
      <td class="stat-box">
        <div class="label">Jami chegirma</div>
        <div class="value">{{ number_format($stats['total_discount'] ?? 0, 0, '.', ' ') }} so'm</div>
      </td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th>Sana</th>
        <th class="text-right">Bemorlar</th>
        <th class="text-right">Daromad (so'm)</th>
        <th class="text-right">Chegirma (so'm)</th>
      </tr>
    </thead>
    <tbody>
      @forelse($daily as $row)
      <tr>
        <td>{{ $row['date'] }}</td>
        <td class="text-right">{{ $row['patients'] }}</td>
        <td class="text-right">{{ number_format($row['revenue'], 0, '.', ' ') }}</td>
        <td class="text-right">{{ number_format($row['discount'] ?? 0, 0, '.', ' ') }}</td>
      </tr>
      @empty
      <tr><td colspan="4" style="text-align:center; color:#999; padding:20px;">Ma'lumot yo'q</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="footer">EverMED CRM &mdash; {{ config('app.url') }}</div>
</body>
</html>
