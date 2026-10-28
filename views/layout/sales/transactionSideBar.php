<?php

//this does things sexy things
// ok this is going to need to wait bit so the market data can be show cuz i can't really do payment without it

$holdings = [""];
$currentStock = $marketData[0];
$idStock = 1;
function formatMoney(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function formatSignedMoney(float $amount): string
{
    $sign = $amount >= 0 ? '+' : '-';
    return $sign . '$' . number_format(abs($amount), 2);
}

function formatSignedPct(float $pct): string
{
    $sign = $pct >= 0 ? '+' : '';
    return $sign . number_format($pct, 2) . '%';
}
?>

<section class="investment-page">
    <div class="investment-layout">
 
        <aside class="investment-sidebar">
 
            <div class="order-ticket" id="orderTicket" data-side="sell"
                 data-symbol="<?= htmlspecialchars($currentStock['symbol']) ?>"
                 data-price="<?= htmlspecialchars((string) $currentStock['price']) ?>">
 
                <div class="order-ticket-side-tabs">
                    <button type="button" class="order-ticket-side-btn" data-side="buy"
                        onclick="setOrderSide('buy')">Buy</button>
                    <button type="button" class="order-ticket-side-btn is-active" data-side="sell"
                        onclick="setOrderSide('sell')">Sell</button>
                </div>
 
                <div class="order-ticket-type-tabs">
                    <button type="button" class="order-ticket-type-btn is-active"
                        onclick="setOrderType(this)">Market</button>
                    <button type="button" class="order-ticket-type-btn" onclick="setOrderType(this)">Limit</button>
                    <button type="button" class="order-ticket-type-btn" onclick="setOrderType(this)">Stop</button>
                    <button type="button" class="order-ticket-type-btn" onclick="setOrderType(this)">Stop Limit</button>
                </div>
 
                <div class="order-ticket-field">
                    <label class="order-ticket-label">Size</label>
                    <div class="order-ticket-input-row">
                        <input type="number" class="order-ticket-input" id="orderSize" placeholder="shares or dollars">
                        <select class="order-ticket-unit" id="orderUnit">
                            <option value="shares" >#</option>
                            <option value="dollars">$</option>
                        </select>
                    </div>
                </div>
 
                <div class="order-ticket-field">
                    <div class="order-ticket-row-label">
                        <label class="order-ticket-label">% Position</label>
                        <span class="order-ticket-slider-value" id="positionPctValue">8%</span>
                    </div>
                    <input type="range" class="order-ticket-slider" id="positionPct" min="0" max="100" value="8"
                        oninput="document.getElementById('positionPctValue').textContent = this.value + '%'">
                </div>
 
                <div class="order-ticket-duration-row">
                    <div class="order-ticket-field">
                        <label class="order-ticket-label">Duration</label>
                        <select class="order-ticket-select">
                            <option>Once</option>
                            <option>Day</option>
                            <option>GTC</option>
                        </select>
                    </div>
                    <div class="order-ticket-field">
                        <label class="order-ticket-label">Total SH</label>
                        <input type="number" class="order-ticket-input" placeholder="--">
                    </div>
                </div>
 
                <div class="order-ticket-exit-row">
                    <button type="button" class="order-ticket-toggle-dot" aria-label="Enable target"></button>
                    <span class="order-ticket-exit-label">Target</span>
                    <span class="order-ticket-exit-value">3.42</span>
                </div>
 
                <div class="order-ticket-exit-row">
                    <button type="button" class="order-ticket-toggle-dot" aria-label="Enable stop"></button>
                    <span class="order-ticket-exit-label">Stop</span>
                    <span class="order-ticket-exit-value">2.57</span>
                    <button type="button" class="order-ticket-pick-btn">Pick</button>
                </div>
 
                <p class="order-ticket-summary" id="orderSummary">
                    Sell Market &middot; <?= htmlspecialchars($currentStock['symbol']) ?> @ ~<?= htmlspecialchars(formatMoney($currentStock['price'])) ?>
                </p>
 
                <p class="order-ticket-feedback" id="orderFeedback" hidden></p>
 
                <div class="order-ticket-actions">
                    <button type="button" class="order-ticket-place-btn" id="orderPlaceBtn" onclick="placeOrder()">Place Sell Market</button>
                    <button type="button" class="order-ticket-max-btn">Sell Max</button>
                </div>
            </div>
 
           
               
            </div>
        </aside>
 
    </div>
</section>
<style>
    .investment-layout {
        display: flex;
        align-items: flex-start;
        gap: 0;
    }

    .investment-page {
        flex: 1;
        min-width: 0;
    }

    .investment-sidebar {
        position: sticky;
        top: 0;
        width: 320px;
        flex-shrink: 0;
        height: 100vh;
        padding: 2.2rem 1.2rem 3rem 0;
        box-sizing: border-box;
        overflow-y: auto;
        border-left: 1px solid rgba(148, 163, 184, 0.13);
    }

    .sidebar-holdings-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
    }

    .sidebar-holding-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.6rem;
        padding-bottom: 0.9rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    }

    .sidebar-holding-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .sidebar-holding-row .card-label {
        display: block;
        margin: 0.15rem 0 0;
    }

    .sidebar-holding-value {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.2rem;
        font-weight: 600;
        color: #f8fafc;
    }

    @media (max-width: 980px) {
        .investment-layout {
            flex-direction: column;
        }

        .investment-sidebar {
            position: static;
            width: 100%;
            height: auto;
            padding: 0 0 1.5rem;
            border-left: none;
            border-top: 1px solid rgba(148, 163, 184, 0.13);
        }
    }

    .order-ticket {
        background: rgba(15, 23, 42, 0.78);
        border: 1px solid rgba(148, 163, 184, 0.13);
        border-radius: 18px;
        padding: 1.1rem;
        margin-bottom: 1.2rem;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.25);
        --danger: #f87171;
        --danger-bg: rgba(248, 113, 113, 0.1);
    }

    .order-ticket-side-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.6rem;
        margin-bottom: 0.9rem;
    }

    .order-ticket-side-btn {
        padding: 0.65rem;
        border-radius: 10px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: transparent;
        color: #94a3b8;
        font-weight: 700;
        cursor: pointer;
    }

    .order-ticket-side-btn[data-side="buy"].is-active {
        border-color: rgba(125, 211, 252, 0.5);
        background: rgba(14, 165, 233, 0.12);
        color: #7dd3fc;
    }

    .order-ticket-side-btn[data-side="sell"].is-active {
        border-color: rgba(248, 113, 113, 0.5);
        background: var(--danger-bg);
        color: var(--danger);
    }

    .order-ticket-type-tabs {
        display: flex;
        gap: 0.3rem;
        margin-bottom: 1.1rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.13);
        overflow-x: auto;
    }

    .order-ticket-type-btn {
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        color: #94a3b8;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 0.5rem 0.4rem;
        cursor: pointer;
        white-space: nowrap;
    }

    .order-ticket-type-btn.is-active {
        color: #7dd3fc;
        border-bottom-color: #7dd3fc;
    }

    .order-ticket-field {
        margin-bottom: 0.9rem;
    }

    .order-ticket-label {
        display: block;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #94a3b8;
        margin-bottom: 0.4rem;
    }

    .order-ticket-input-row {
        display: flex;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 10px;
        overflow: hidden;
    }

    .order-ticket-input,
    .order-ticket-select {
        background: rgba(5, 11, 20, 0.5);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 10px;
        color: #f8fafc;
        padding: 0.6rem 0.7rem;
        font-size: 0.9rem;
        width: 100%;
        box-sizing: border-box;
    }

    .order-ticket-input-row .order-ticket-input {
        border: none;
        border-radius: 0;
        flex: 1;
    }

    .order-ticket-unit {
        background: rgba(148, 163, 184, 0.08);
        border: none;
        border-left: 1px solid rgba(148, 163, 184, 0.18);
        color: #f8fafc;
        padding: 0 0.6rem;
    }

    .order-ticket-row-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.4rem;
    }

    .order-ticket-slider-value {
        color: #f8fafc;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .order-ticket-slider {
        width: 100%;
        accent-color: #7dd3fc;
    }

    .order-ticket-duration-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.7rem;
    }

    .order-ticket-exit-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.5rem 0;
    }

    .order-ticket-toggle-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: rgba(148, 163, 184, 0.3);
        border: none;
        cursor: pointer;
        flex-shrink: 0;
    }

    .order-ticket-toggle-dot.is-on {
        background: #7dd3fc;
    }

    .order-ticket-exit-label {
        color: #f8fafc;
        font-weight: 600;
        font-size: 0.85rem;
        flex: 1;
    }

    .order-ticket-exit-value {
        color: #cbd5e1;
        font-size: 0.9rem;
    }

    .order-ticket-pick-btn {
        background: rgba(148, 163, 184, 0.1);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 8px;
        color: #94a3b8;
        font-size: 0.75rem;
        padding: 0.3rem 0.55rem;
        cursor: pointer;
    }

    .order-ticket-summary {
        margin: 0.6rem 0 1rem;
        color: #94a3b8;
        font-size: 0.8rem;
    }

    .order-ticket-actions {
        display: flex;
        gap: 0.6rem;
    }

    .order-ticket-place-btn {
        flex: 1;
        padding: 0.85rem;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(14, 165, 233, 0.12);
        color: #7dd3fc;
        font-weight: 700;
        cursor: pointer;
    }

    .order-ticket-max-btn {
        padding: 0.85rem 1rem;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(148, 163, 184, 0.06);
        color: #94a3b8;
        font-weight: 700;
        cursor: pointer;
    }

    /* Sell side recolors the ticket's action buttons */
    .order-ticket[data-side="sell"] .order-ticket-place-btn,
    .order-ticket[data-side="sell"] .order-ticket-max-btn {
        border-color: rgba(248, 113, 113, 0.4);
        background: var(--danger-bg);
        color: var(--danger);
    }
</style>
<script>
    // have i told you guys i like javascript lul

    function setOrderSide(side) {
        const ticket = document.getElementById('orderTicket');
        ticket.dataset.side = side;
 
        ticket.querySelectorAll('.order-ticket-side-btn').forEach(btn => {
            btn.classList.toggle('is-active', btn.dataset.side === side);
        });
        const active = ticket.querySelector('.order-ticket-type-btn.is-active')?.textContent.trim() || 'Market';
        const label = side === 'buy' ? 'Buy' : 'Sell';
 
        document.getElementById('orderPlaceBtn').textContent = `Place ${label} ${active}`;
        document.querySelector('.order-ticket-max-btn').textContent = side === 'buy' ? 'Buy Max' : 'Sell Max';
        updateSummary();
    }
 
    function setOrderType(btn) {
        const ticket = document.getElementById('orderTicket');
        ticket.querySelectorAll('.order-ticket-type-btn').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
 
        const side = ticket.dataset.side === 'buy' ? 'Buy' : 'Sell';
        document.getElementById('orderPlaceBtn').textContent = `Place ${side} ${btn.textContent.trim()}`;
        updateSummary();
    }
 
    function updateSummary() {
        const ticket = document.getElementById('orderTicket');
        const side = ticket.dataset.side === 'buy' ? 'Buy' : 'Sell';
        const type = ticket.querySelector('.order-ticket-type-btn.is-active')?.textContent.trim() || 'Market';
        const symbol = ticket.dataset.symbol;
        const price = parseFloat(ticket.dataset.price).toFixed(2);
 
        document.getElementById('orderSummary').textContent = `${side} ${type} · ${symbol} @ ~$${price}`;
    }
 
    async function placeOrder() {
        const ticket = document.getElementById('orderTicket');
        const feedback = document.getElementById('orderFeedback');
        const placeBtn = document.getElementById('orderPlaceBtn');
 
        const side = ticket.dataset.side;
        const idStock = parseInt(ticket.dataset.idStock, 10);
        const unit = document.getElementById('orderUnit').value; 
        const amount = parseFloat(document.getElementById('orderSize').value);
 
        feedback.hidden = true;
        feedback.className = 'order-ticket-feedback';
 
        if (!amount || amount <= 0) {
            feedback.textContent = 'Enter a size greater than 0.';
            feedback.classList.add('is-error');
            feedback.hidden = false;
            return;
        }
 
        placeBtn.disabled = true;
        const originalLabel = placeBtn.textContent;
        placeBtn.textContent = 'Placing…';
 
        try {
            const res = await fetch('src/DAL/placeOrder.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ side, idStock, amount, unit }),
            });
 
            const data = await res.json();
 
            feedback.textContent = data.message;
            feedback.classList.add(data.success ? 'is-success' : 'is-error');
            feedback.hidden = false;
        } catch (err) {
            feedback.textContent = 'Network error placing order.';
            feedback.classList.add('is-error');
            feedback.hidden = false;
        } finally {
            placeBtn.disabled = false;
            placeBtn.textContent = originalLabel;
        }
    }
 
    document.querySelectorAll('.order-ticket-toggle-dot').forEach(dot => {
        dot.addEventListener('click', () => dot.classList.toggle('is-on'));
    });
</script>