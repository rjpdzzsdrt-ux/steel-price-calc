<?php
/**
 * Plugin Name: Steel.ee Price Calculator
 * Description: Hinnakalkulaator (JM-põhine valem) + KM 24% + WPForms (hind ID=17, parameetrid steel-orient-params, sõnum auto). Slider A/B + primary värv + CSS eraldi.
 * Version: 2.5.0
 */

if (!defined('ABSPATH')) exit;

class Steel_EE_Price_Calculator {
  private static $instance = null;
  private static $needs_assets = false;

  public static function instance() {
    if (self::$instance === null) self::$instance = new self();
    return self::$instance;
  }

  private function __construct() {
    add_shortcode('steel_price_calc', [$this, 'render_shortcode']);
    add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
    add_action('wp_footer', [$this, 'maybe_print_js'], 50);
  }

  public function maybe_enqueue_assets() {
    if (!self::$needs_assets) return;

    $css_handle = 'steel-price-calculator';
    wp_register_style(
      $css_handle,
      plugin_dir_url(__FILE__) . 'steel-price-calculator.css',
      [],
      '2.5.0'
    );
    wp_enqueue_style($css_handle);
  }

  public function render_shortcode() {
    self::$needs_assets = true;

    // Slider range (mm) - muuda vajadusel
    $min = 50;
    $max = 2000;
    $step = 1;
    $defaultA = 1000;
    $defaultB = 200;

    // Unique instance id (supports multiple calculators on the same page)
    $uid = 'spc_' . wp_unique_id();

    ob_start(); ?>
    <div class="steel-calc" data-spc="<?php echo esc_attr($uid); ?>">
      <div class="steel-calc__header">
        <h3 class="steel-calc__title">Saa hind 30 sekundiga – sisesta mõõdud</h3>
        <div class="steel-calc__subtitle">
          Orienteeruv hind kohe ekraanile (sisaldab käibemaksu).
        </div>
      </div>

      <!-- Haar A -->
      <div class="steel-calc__field">
        <div class="steel-calc__label-row">
          <label class="steel-calc__label" for="<?php echo esc_attr($uid); ?>_a_num">Haar A (mm)</label>
          <input
            id="<?php echo esc_attr($uid); ?>_a_num"
            class="steel-calc__number"
            type="number"
            inputmode="numeric"
            min="<?php echo (int)$min; ?>"
            max="<?php echo (int)$max; ?>"
            step="<?php echo (int)$step; ?>"
            value="<?php echo (int)$defaultA; ?>"
            data-spc-a-number
          >
        </div>

        <input
          class="steel-calc__range"
          type="range"
          min="<?php echo (int)$min; ?>"
          max="<?php echo (int)$max; ?>"
          step="<?php echo (int)$step; ?>"
          value="<?php echo (int)$defaultA; ?>"
          data-spc-a-range
          aria-label="Haar A slider"
        >
      </div>

      <!-- Haar B -->
      <div class="steel-calc__field">
        <div class="steel-calc__label-row">
          <label class="steel-calc__label" for="<?php echo esc_attr($uid); ?>_b_num">Haar B (mm)</label>
          <input
            id="<?php echo esc_attr($uid); ?>_b_num"
            class="steel-calc__number"
            type="number"
            inputmode="numeric"
            min="<?php echo (int)$min; ?>"
            max="<?php echo (int)$max; ?>"
            step="<?php echo (int)$step; ?>"
            value="<?php echo (int)$defaultB; ?>"
            data-spc-b-number
          >
        </div>

        <input
          class="steel-calc__range"
          type="range"
          min="<?php echo (int)$min; ?>"
          max="<?php echo (int)$max; ?>"
          step="<?php echo (int)$step; ?>"
          value="<?php echo (int)$defaultB; ?>"
          data-spc-b-range
          aria-label="Haar B slider"
        >
      </div>

      <!-- Kogus -->
      <div class="steel-calc__field">
        <div class="steel-calc__label-row">
          <label class="steel-calc__label" for="<?php echo esc_attr($uid); ?>_qty">Kogus (jm)</label>
          <input
            id="<?php echo esc_attr($uid); ?>_qty"
            class="steel-calc__number"
            type="number"
            min="0.01"
            step="0.01"
            value="1"
            inputmode="decimal"
            data-spc-qty
          >
        </div>
      </div>

      <!-- Materjal -->
      <div class="steel-calc__field">
        <label class="steel-calc__label" for="<?php echo esc_attr($uid); ?>_mat">Materjal</label>
        <select id="<?php echo esc_attr($uid); ?>_mat" class="steel-calc__select" data-spc-mat>
          <option value="tsink">Tsink</option>
          <option value="alutsink">Alutsink</option>
          <option value="pol">POL</option>
          <option value="pur" selected>PUR</option>
          <option value="pur_matt">PUR MATT</option>
        </select>
      </div>

      <!-- RAL -->
      <div class="steel-calc__field">
        <label class="steel-calc__label" for="<?php echo esc_attr($uid); ?>_ral">Toon (RAL) <span class="steel-calc__muted">(valikuline)</span></label>
        <input
          id="<?php echo esc_attr($uid); ?>_ral"
          class="steel-calc__input"
          type="text"
          placeholder="nt RAL7016"
          data-spc-ral
        >
      </div>

      <button type="button" class="steel-calc__btn" data-spc-calc>Arvuta hind</button>

      <div class="steel-calc__result">
        <div class="steel-calc__price" data-spc-price>—</div>
        <div class="steel-calc__hint">Hind sisaldab käibemaksu (KM-ga).</div>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  public function maybe_print_js() {
    if (!self::$needs_assets) return;
    ?>
    <script>
    (function(){
      function steelFindWPFormsMessageField(){
        var candidates = [];
        document.querySelectorAll("form.wpforms-form textarea").forEach(function(t){ candidates.push(t); });
        document.querySelectorAll("textarea").forEach(function(t){ candidates.push(t); });
        document.querySelectorAll("form.wpforms-form input[type='text']").forEach(function(i){ candidates.push(i); });

        for (var i=0;i<candidates.length;i++){
          var el = candidates[i];
          var name = (el.getAttribute("name") || "").toLowerCase();
          var id = (el.getAttribute("id") || "").toLowerCase();
          if(name.indexOf("message")>-1 || name.indexOf("textarea")>-1 || id.indexOf("message")>-1){
            return el;
          }
        }
        for (var j=0;j<candidates.length;j++){
          if(candidates[j].tagName.toLowerCase()==="textarea") return candidates[j];
        }
        return null;
      }

      function steelSetMessageIfEmpty(text){
        var msg = steelFindWPFormsMessageField();
        if(!msg) return;
        var current = (msg.value || "").trim();
        if(current.length > 0) return;
        msg.value = text;
        msg.dispatchEvent(new Event("input", {bubbles:true}));
        msg.dispatchEvent(new Event("change", {bubbles:true}));
      }

      function clamp(n, min, max){
        n = Number(n || 0);
        if (isNaN(n)) n = min;
        return Math.max(min, Math.min(max, n));
      }

      function matNameFromKey(key){
        if(key==="tsink") return "Tsink";
        if(key==="alutsink") return "Alutsink";
        if(key==="pol") return "POL";
        if(key==="pur") return "PUR";
        if(key==="pur_matt") return "PUR MATT";
        return "";
      }

      function m2PriceFromMat(key){
        // sama hinnaloogika mis sul varem
        if(key === "tsink") return 7;
        if(key === "alutsink") return 7;
        if(key === "pol") return 7.5;
        if(key === "pur") return 8;
        if(key === "pur_matt") return 11;
        return 8;
      }

      function bindRangeNumber(container, rangeSel, numberSel){
        var r = container.querySelector(rangeSel);
        var n = container.querySelector(numberSel);
        if(!r || !n) return;

        var min = Number(r.min || 0);
        var max = Number(r.max || 999999);

        function syncFromRange(){
          n.value = r.value;
        }
        function syncFromNumber(){
          var v = clamp(n.value, min, max);
          n.value = v;
          r.value = v;
        }

        r.addEventListener("input", syncFromRange);
        n.addEventListener("input", syncFromNumber);

        syncFromRange();
      }

      function calcForContainer(container){
        var aNum = container.querySelector("[data-spc-a-number]");
        var bNum = container.querySelector("[data-spc-b-number]");
        var qty  = container.querySelector("[data-spc-qty]");
        var mat  = container.querySelector("[data-spc-mat]");
        var ral  = container.querySelector("[data-spc-ral]");
        var out  = container.querySelector("[data-spc-price]");

        if(!aNum || !bNum || !qty || !mat || !ral || !out) return;

        var haarA = Number(aNum.value || 0);
        var haarB = Number(bNum.value || 0);
        var qtyJm = Number(qty.value || 0);
        var matKey = mat.value;
        var ralVal = (ral.value || "").trim();

        if(haarA<=0 || haarB<=0 || qtyJm<=0){
          out.textContent = "Palun sisesta mõõdud ja kogus";
          return;
        }

        var m2Price = m2PriceFromMat(matKey);
        var perJmFixedFee = 2.5;

        var jmUnitPriceNet = (((haarA + haarB) / 1000) * m2Price) + perJmFixedFee;
        var totalNet = jmUnitPriceNet * qtyJm;

        var vatRate = 1.24;
        var totalGross = totalNet * vatRate;
        var priceGross = totalGross.toFixed(2);

        out.textContent = priceGross.replace(".", ",") + " € (KM-ga)";

        var matName = matNameFromKey(matKey);

        var paramsObj = {
          haarA_mm: haarA,
          haarB_mm: haarB,
          kogus_jm: qtyJm,
          materjal: matName,
          ral: ralVal,
          m2_hind_eur: m2Price,
          fikseeritud_lisa_eur_jm: perJmFixedFee,
          jm_uhikuhind_net_eur_jm: Number(jmUnitPriceNet.toFixed(4)),
          hind_net_eur: Number(totalNet.toFixed(2)),
          km_kordaja: vatRate,
          hind_kmga_eur: Number(priceGross)
        };
        var params = JSON.stringify(paramsObj);

        // WPForms: hind field ID
        var wpformsPriceId = 17;

        document.querySelectorAll(
          'input[name="wpforms[fields][' + wpformsPriceId + ']"], textarea[name="wpforms[fields][' + wpformsPriceId + ']"]'
        ).forEach(function(el){
          el.value = priceGross;
          el.dispatchEvent(new Event("input", {bubbles:true}));
          el.dispatchEvent(new Event("change", {bubbles:true}));
        });

        // hidden params
        document.querySelectorAll(
          "input.steel-orient-params, textarea.steel-orient-params, .steel-orient-params input, .steel-orient-params textarea"
        ).forEach(function(el){
          el.value = params;
          el.dispatchEvent(new Event("input", {bubbles:true}));
          el.dispatchEvent(new Event("change", {bubbles:true}));
        });

        // hidden hind
        document.querySelectorAll(
          "input.steel-orient-hind, textarea.steel-orient-hind, .steel-orient-hind input, .steel-orient-hind textarea"
        ).forEach(function(el){
          el.value = priceGross;
          el.dispatchEvent(new Event("input", {bubbles:true}));
          el.dispatchEvent(new Event("change", {bubbles:true}));
        });

        var msgText =
          "Soovin pakkumist.\n" +
          "Haar A: " + haarA + " mm, Haar B: " + haarB + " mm\n" +
          "Kogus: " + qtyJm + " jm\n" +
          "Materjal: " + matName + (ralVal ? (", RAL: " + ralVal) : "") + "\n" +
          "Orienteeruv hind (KM-ga): " + priceGross.replace(".", ",") + " €";

        steelSetMessageIfEmpty(msgText);
      }

      function init(){
        document.querySelectorAll(".steel-calc").forEach(function(container){
          bindRangeNumber(container, "[data-spc-a-range]", "[data-spc-a-number]");
          bindRangeNumber(container, "[data-spc-b-range]", "[data-spc-b-number]");

          var btn = container.querySelector("[data-spc-calc]");
          if(btn){
            btn.addEventListener("click", function(e){
              e.preventDefault();
              calcForContainer(container);
            });
          }
        });
      }

      if(document.readyState === "loading"){
        document.addEventListener("DOMContentLoaded", init);
      } else {
        init();
      }
    })();
    </script>
    <?php
  }
}

Steel_EE_Price_Calculator::instance();
