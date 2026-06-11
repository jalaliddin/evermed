<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; padding: 30px; }
  h1 { font-size: 20px; color: #1565C0; margin-bottom: 4px; }
  .subtitle { color: #666; font-size: 11px; margin-bottom: 24px; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #1565C0; color: white; padding: 8px 10px; text-align: left; font-size: 11px; }
  td { padding: 8px 10px; border-bottom: 1px solid #e8e8e8; font-size: 11px; }
  tr:nth-child(even) td { background: #f8f9ff; }
  .text-right { text-align: right; }
  .rank { font-weight: bold; color: #1565C0; }
  .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>
  <h1>Shifokorlar hisoboti</h1>
  <div class="subtitle">Hisobot sanasi: {{ now()->format('d.m.Y H:i') }}</div>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Shifokor</th>
        <th>Mutaxassislik</th>
        <th class="text-right">Qabullar</th>
        <th class="text-right">Daromad (so'm)</th>
        <th class="text-right">O'rtacha chek</th>
      </tr>
    </thead>
    <tbody>
      @forelse($doctors as $i => $row)
      <tr>
        <td class="rank">{{ $i + 1 }}</td>
        <td>{{ $row['doctor']['user']['name'] ?? '-' }}</td>
        <td>{{ $row['doctor']['specialization'] ?? '-' }}</td>
        <td class="text-right">{{ $row['visits_count'] }}</td>
        <td class="text-right">{{ number_format($row['revenue'], 0, '.', ' ') }}</td>
        <td class="text-right">{{ number_format($row['avg_check'], 0, '.', ' ') }}</td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center; color:#999; padding:20px;">Ma'lumot yo'q</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="footer">EverMED CRM &mdash; {{ config('app.url') }}</div>
</body>
</html>
