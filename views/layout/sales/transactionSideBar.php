<?php 

?>

<section class="investement-page">
    <div  class="investement-layout">

        <aside class="investment-sidebar">
 
            <div class="order-ticket" id="orderTicket" data-side="sell">
                <div class="order-ticket-side-tabs">
                    <button type="button" class="order-ticket-side-btn" data-side="buy" onclick="setOrderSide('buy')">Buy</button>
                    <button type="button" class="order-ticket-side-btn is-active" data-side="sell" onclick="setOrderSide('sell')">Sell</button>
                </div>
 
                <div class="order-ticket-type-tabs">
                    <button type="button" class="order-ticket-type-btn is-active" onclick="setOrderType(this)">Market</button>
                    <button type="button" class="order-ticket-type-btn" onclick="setOrderType(this)">Limit</button>
                    <button type="button" class="order-ticket-type-btn" onclick="setOrderType(this)">Stop</button>
                    <button type="button" class="order-ticket-type-btn" onclick="setOrderType(this)">Stop Limit</button>
                </div>
 
                <div class="order-ticket-field">
                    <label class="order-ticket-label">Size</label>
                    <div class="order-ticket-input-row">
                        <input type="text" class="order-ticket-input" placeholder="shares or dollars">
                        <select class="order-ticket-unit">
                            <option value="shares">#</option>
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
                        <input type="text" class="order-ticket-input" placeholder="--">
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
 
                <p class="order-ticket-summary" id="orderSummary">Sell Market | 141.04K sh @ ~$2.78</p>
 
                <div class="order-ticket-actions">
                    <button type="button" class="order-ticket-place-btn" id="orderPlaceBtn">Place Sell Market</button>
                    <button type="button" class="order-ticket-max-btn">Sell Max</button>
                </div>
            </div>
 
            <div class="investment-panel">
                <div class="panel-header">
                    <h3>Holdings</h3>
                    <span><?= count($holdings) ?> position<?= count($holdings) === 1 ? '' : 's' ?></span>
                </div>
 
                <?php if (!$hasHoldings || empty($holdings)): ?>
                    <div class="empty-state">
                        <p>You don't have any positions yet. Place your first order to get started.</p>
                    </div>
                <?php else: ?>
                    <ul class="sidebar-holdings-list">
                        <?php foreach ($holdings as $h): ?>
                            <li class="sidebar-holding-row">
                                <div>
                                    <strong><?= htmlspecialchars($h['symbol']) ?></strong>
                                    <span class="card-label"><?= htmlspecialchars((string) $h['shares']) ?> sh</span>
                                </div>
                                <div class="sidebar-holding-value">
                                    <span><?= htmlspecialchars(formatMoney($h['value'])) ?></span>
                                    <span class="<?= $h['changePct'] >= 0 ? 'positive-change' : 'neutral-text' ?>">
                                        <?= htmlspecialchars(formatSignedPct($h['changePct'])) ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>
           
    </div>
</section>