<?php
/**
 * Plugin Name: Steel.ee Price Calculator
 * Description: Hinnakalkulaator (JM-põhine valem) + KM 24% + WPForms auto-täide + GTM dataLayer events (calc + lead submit)
 * Version: 2.5.0
 */

if (!defined('ABSPATH')) exit;

add_shortcode('steel_price_calc', function () {

  $html = <<<HTML
<div class="steel-price-calc" style="border:1px solid #ddd;padding:16px;border-radius:10px;width:100%;box-sizing:border-box">
  <h3 style="margin:0 0 6px 0">Saa hind 30 sekundiga – sisesta mõõdud</h3>
  <div style="font-size:13px;opacity:.75;margin-bottom:12px">
    Orienteeruv hind kohe ekraanile (sisaldab käibemaksu).
  </div>

  <label style="display:block;margin-bottom:8px">Pikkus (mm) — haar A<br>
    <input id="spc_l" type="number" min="1" inputmode="numeric"
      style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;box-sizing:border-box">
  </label>

  <label style="display:block;margin-bottom:8px">Laius (mm) — haar B<br>
    <input id="spc_w" type="number" min="1" inputmode="numeric"
      style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;box-sizing:border-box">
  </label>

  <label style="display:block;margin-bottom:8px">Kogus (jm)<br>
    <input id="spc_q" type="number" min="0.01" step="0.01" value="1" inputmode="decimal"
      style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;box-sizing:border-box">
  </label>

  <label style="display:block;margin-bottom:8px">Materjal<br>
    <select id="spc_mat" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;box-sizing:border-box">
      <option value="tsink">Tsink</option>
      <option value="alutsink">Alutsink</option>
      <option value="pol">POL</option>
      <option value="pur" selected>PUR</option>
      <option value="pur_matt">PUR MATT</option>
    </select>
  </label>

  <label style="display:block;margin-bottom:12px">Toon (RAL) (valikuline)<br>
    <input id="spc_ral" placeholder="nt RAL7016"
      style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;box-sizing:border-box">
  </label>

  <div style="display:flex;justify-content:center">
    <button type="button"
      class="elementor-button elementor-button-link elementor-size-md steel-calc-btn"
      onclick="steelCalcPrice()"
      style="padding:12px 18px;border-radius:10px;cursor:pointer">
      <span class="elementor-button-content-wrapper">
        <span class="elementor-button-text">Arvuta hind</span>
      </span>
    </button>
  </div>

  <div style="margin-top:14px;text-align:center">
    <div id="spc_price" style="font-size:22px;font-weight:700">—</div>
    <div style="font-size:12px;opacity:.65;margin-top:6px">
      Hind sisaldab käibemaksu (KM-ga).
    </div>
  </div>
</div>

<script>
(function(){
  // =========================
  // 0) GTM helper
  // =========================
  function steelDLPush(obj){
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(obj);
  }

  // (VALIKULINE) Kui tahad trackida ainult kindlat WPForms vormi, pane siia form ID.
  // Näide: var STEEL_WPFORMS_ONLY_ID = 1234;
  // Kui jätad null/0, siis trackib kõiki WPForms edukaid submite.
  var STEEL_WPFORMS_ONLY_ID = 0;

  // =========================
  // 1) WPForms message field finder (sinu olemasolev loogika)
  // =========================
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

  // =========================
  // 2) Kalkulaatori arvutus + GTM event
  // =========================
  window.steelCalcPrice = function(){
    var l = Number(document.getElementById("spc_l").value || 0);
    var w = Number(document.getElementById("spc_w").value || 0);
    var qtyJm = Number(document.getElementById("spc_q").value || 0);
    var mat = document.getElementById("spc_mat").value;
    var ral = (document.getElementById("spc_ral").value || "").trim();

    if(l<=0 || w<=0 || qtyJm<=0){
      document.getElementById("spc_price").innerHTML = "Palun sisesta mõõdud ja kogus";
      return;
    }

    var haarA = l + 10;
    var haarB = w + 10;

    var m2Price = 8;
    if(mat === "tsink")     m2Price = 6.5;
    if(mat === "alutsink")  m2Price = 6.5;
    if(mat === "pol")       m2Price = 7.5;
    if(mat === "pur")       m2Price = 8.5;
    if(mat === "pur_matt")  m2Price = 11.5;

    var perJmFixedFee = 2.5;
    var jmUnitPriceNet = (((haarA + haarB) / 1000) * m2Price) + perJmFixedFee;
    var totalNet = jmUnitPriceNet * qtyJm;

    var vatRate = 1.24;
    var totalGross = totalNet * vatRate;
    var priceGross = totalGross.toFixed(2);

    document.getElementById("spc_price").innerHTML = priceGross.replace(".", ",") + " € (KM-ga)";

    var matName = "";
    if(mat==="tsink") matName="Tsink";
    if(mat==="alutsink") matName="Alutsink";
    if(mat==="pol") matName="POL";
    if(mat==="pur") matName="PUR";
    if(mat==="pur_matt") matName="PUR MATT";

    var paramsObj = {
      pikkus_mm: l,
      laius_mm: w,
      kogus_jm: qtyJm,
      haarA_mm: haarA,
      haarB_mm: haarB,
      materjal: matName,
      ral: ral,
      m2_hind_eur: m2Price,
      fikseeritud_lisa_eur_jm: perJmFixedFee,
      jm_uhikuhind_net_eur_jm: Number(jmUnitPriceNet.toFixed(4)),
      hind_net_eur: Number(totalNet.toFixed(2)),
      km_kordaja: vatRate,
      hind_kmga_eur: Number(priceGross)
    };

    // --- GTM: kalkulaatori event koos kõigi parameetritega (ÕIGE viis) ---
    steelDLPush({
      event: "steel_price_calc",
      steel_calc: paramsObj
    });

    // --- WPForms väljade automaatne täitmine (sinu olemasolev loogika) ---
    var params = JSON.stringify(paramsObj);

    var wpformsPriceId = 17;
    document.querySelectorAll('input[name="wpforms[fields][' + wpformsPriceId + ']"], textarea[name="wpforms[fields][' + wpformsPriceId + ']"]').forEach(function(el){
      el.value = priceGross;
      el.dispatchEvent(new Event("input", {bubbles:true}));
      el.dispatchEvent(new Event("change", {bubbles:true}));
    });

    document.querySelectorAll("input.steel-orient-params, textarea.steel-orient-params, .steel-orient-params input, .steel-orient-params textarea").forEach(function(el){
      el.value = params;
      el.dispatchEvent(new Event("input", {bubbles:true}));
      el.dispatchEvent(new Event("change", {bubbles:true}));
    });

    document.querySelectorAll("input.steel-orient-hind, textarea.steel-orient-hind, .steel-orient-hind input, .steel-orient-hind textarea").forEach(function(el){
      el.value = priceGross;
      el.dispatchEvent(new Event("input", {bubbles:true}));
      el.dispatchEvent(new Event("change", {bubbles:true}));
    });

    var msgText =
      "Soovin pakkumist.\\n" +
      "Pikkus: " + l + " mm, Laius: " + w + " mm\\n" +
      "Kogus: " + qtyJm + " jm\\n" +
      "Materjal: " + matName + (ral ? (", RAL: " + ral) : "") + "\\n" +
      "Orienteeruv hind (KM-ga): " + priceGross.replace(".", ",") + " €";
    steelSetMessageIfEmpty(msgText);
  };

  // =========================
  // 3) WPForms lead submit success -> GTM event (ÕIGE viis)
  // =========================
  function steelPushLeadSubmit(e){
    var formId =
      (e && e.detail && (e.detail.formId || e.detail.id)) ||
      (e && e.formId) ||
      undefined;

    // Kui kasutad filtreerimist kindla form_id järgi
    if (STEEL_WPFORMS_ONLY_ID && String(formId) !== String(STEEL_WPFORMS_ONLY_ID)) {
      return;
    }

    steelDLPush({
      event: "steel_lead_submit",
      steel_lead: {
        source: "wpforms",
        form_id: formId || ""
      }
    });
  }

  // WPForms AJAX submit success event (conversion only)
  document.addEventListener("wpformsAjaxSubmitSuccess", function(e){
    steelPushLeadSubmit(e);
  });

})();
</script>
HTML;

  return $html;
});
