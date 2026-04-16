@props(['label', 'value', 'bgVar', 'dotVar', 'urgent' => false, 'status' => ''])

<div class="inv-risk-card-new" style="background: hsl(var({{ $bgVar }}));">
    <div class="inv-risk-card-header">
        <span class="inv-risk-card-label">{{ $label }}</span>
        @if($urgent)
            <span class="inv-risk-dot-wrap">
                <span class="inv-risk-dot-ping" style="background: hsl(var({{ $dotVar }}));"></span>
                <span class="inv-risk-dot-core" style="background: hsl(var({{ $dotVar }}));"></span>
            </span>
        @else
            <span class="inv-risk-dot" style="background: hsl(var({{ $dotVar }}));"></span>
        @endif
    </div>
    <div class="inv-risk-card-value">{{ $value }}</div>
    @if($status)
        <div class="inv-risk-card-status">{{ $status }}</div>
    @endif
</div>
