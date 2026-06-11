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
  .badge-in  { background: #e8f5e9; color: #2e7d32; padding: 2px 8px; border-radius: 4px; font-size: 10px; }
  .badge-out { background: #fce4ec; color: #c62828; padding: 2px 8px; border-radius: 4px; font-size: 10px; }
  .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>
  <h1>Inventar hisoboti</h1>
  <div class="subtitle">Hisobot sanasi: {{ now()->format('d.m.Y H:i') }}</div>

  @if(!empty($topUsed) && count($topUsed) > 0)
  <h2>Ko'p ishlatiladigan mahsulotlar</h2>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Mahsulot</th>
        <th class="text-right">Sarflandi</th>
      </tr>
    </thead>
    <tbody>
      @foreach($topUsed as $i => $item)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $item['name'] ?? $item->name }}</td>
        <td class="text-right">{{ $item['total_used'] ?? $item->total_used }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  <h2>Harakatlar tarixi</h2>
  <table>
    <thead>
      <tr>
        <th>Sana</th>
        <th>Mahsulot</th>
        <th>Tur</th>
        <th class="text-right">Miqdor</th>
        <th>Izoh</th>
      </tr>
    </thead>
    <tbody>
      @forelse($transactions as $tx)
      <tr>
        <td>{{ \Carbon\Carbon::parse($tx['created_at'] ?? $tx->created_at)->format('d.m.Y') }}</td>
        <td>{{ $tx['item']['name'] ?? ($tx->item->name ?? '-') }}</td>
        <td>
          @if(($tx['type'] ?? $tx->type) === 'in')
            <span class="badge-in">Kirim</span>
          @else
            <span class="badge-out">Chiqim</span>
          @endif
        </td>
        <td class="text-right">{{ $tx['quantity'] ?? $tx->quantity }}</td>
        <td>{{ $tx['notes'] ?? $tx->notes ?? '' }}</td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center; color:#999; padding:20px;">Ma'lumot yo'q</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="footer">EverMED CRM &mdash; {{ config('app.url') }}</div>
</body>
</html>
