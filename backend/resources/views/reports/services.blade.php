<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; padding: 30px; }
  h1 { font-size: 20px; color: #1565C0; margin-bottom: 4px; }
  .subtitle { color: #666; font-size: 11px; margin-bottom: 24px; }
  h2 { font-size: 14px; color: #333; margin: 20px 0 10px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  th { background: #1565C0; color: white; padding: 8px 10px; text-align: left; font-size: 11px; }
  td { padding: 7px 10px; border-bottom: 1px solid #e8e8e8; font-size: 11px; }
  tr:nth-child(even) td { background: #f8f9ff; }
  .text-right { text-align: right; }
  .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>
  <h1>Xizmatlar hisoboti</h1>
  <div class="subtitle">Hisobot sanasi: {{ now()->format('d.m.Y H:i') }}</div>

  <h2>Top 10 xizmat</h2>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Xizmat nomi</th>
        <th class="text-right">Soni</th>
        <th class="text-right">Daromad (so'm)</th>
      </tr>
    </thead>
    <tbody>
      @forelse($services as $i => $svc)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $svc['name'] ?? $svc->name }}</td>
        <td class="text-right">{{ $svc['count'] ?? $svc->count }}</td>
        <td class="text-right">{{ number_format($svc['revenue'] ?? $svc->revenue, 0, '.', ' ') }}</td>
      </tr>
      @empty
      <tr><td colspan="4" style="text-align:center; color:#999; padding:20px;">Ma'lumot yo'q</td></tr>
      @endforelse
    </tbody>
  </table>

  @if(!empty($categories) && count($categories) > 0)
  <h2>Kategoriyalar bo'yicha</h2>
  <table>
    <thead>
      <tr>
        <th>Kategoriya</th>
        <th class="text-right">Daromad (so'm)</th>
      </tr>
    </thead>
    <tbody>
      @foreach($categories as $cat)
      <tr>
        <td>{{ $cat['name'] ?? $cat->name }}</td>
        <td class="text-right">{{ number_format($cat['revenue'] ?? $cat->revenue, 0, '.', ' ') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  <div class="footer">EverMED CRM &mdash; {{ config('app.url') }}</div>
</body>
</html>
