<?php
/**
 * Plugin Name: Steel.ee Price Calculator
 * Description: Hinnakalkulaator (JM-põhine valem) + WPForms (hind ID=17, parameetrid steel-orient-params, sõnum auto)
 * Version: 2.4.1
 */

if (!defined('ABSPATH')) exit;

add_shortcode('steel_price_calc', function () {

  $html = <<<HTML
<div style="border:1px solid #ddd;padding:16px;border-radius:10px;max-width:520px">
  <h3 style="margin:0 0 6px 0">Saa hind 30 sekundiga – sisesta mõõdud</h3>
  <div style="font-size:13px;opacity:.75;margin-bottom:12px">
    Orienteeruv hind kohe ekraanile. Soovi korral saada päring – lisame mõõdud automaatselt kaasa.
  </div>

  <label style="display:block;margin-bottom:8px">Pikkus (mm) — haar A<br>
    <input id="spc_l" type="number" min="1" inputmode="numeric"
      style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
  </label>

  <label style="display:block;margin-bottom:8px">Laius (mm) — haar B<br>
    <input id="spc_w" type="number" min="1" inputmode="numeric"
      style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
  </label>

  <label style="display:block;margin-bottom:8px">Kogus (jm)<br>
    <input id="spc_q" type="number" min="0.01" step="0.01" value="1" inputmode="decimal"
      style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
  </label>

  <label style="display:block;margin-bottom:8px">Materjal<br>
    <select id="spc_mat" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
      <option value="tsink">Tsink</option>
      <option value="alutsink">Alutsink</option>
      <option value="pol">POL</option>
      <option value="pur" selected>PUR</option>
      <option value="pur_matt">PUR MATT</option>
    </select>
  </label>

  <label style="display:block;margin-bottom:12px">Toon (RAL) (valikuline)<br>
    <input id="spc_ral" placeholder="nt RAL7016"
      style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
  </label>

  <button type="button" onclick="steelCalcPrice()"
    style="padding:10px 14px;border-radius:10px;border:0;cursor:pointer">
    Arvuta hind
  </button>

  <div style="margin-top:14px">
    <div id="spc_price" style="font-size:22px;font-weight:700">—</div>
    <div style="font-size:12px;opacity:.65;margin-top:6px">
      Orienteeruv hind. Täpne hind kinnitatakse pakkumisel.
    </div>
  </div>
</div>

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

    // Haar A ja Haar B: kummalegi +10mm
    var haarA = l + 10;
    var haarB = w + 10;

    // Materjali m2 hinnad (sinu hinnad)
    var m2Price = 8; // PUR default
    if(mat === "tsink")     m2Price = 7;
    if(mat === "alutsink") m2Price = 7;
    if(mat === "pol")      m2Price = 7.5;
    if(mat === "pur")      m2Price = 8;
    if(mat === "pur_matt") m2Price = 11;

    // Sinu JM-põhine valem:
    // jm_unit_price = (((haarA + haarB) / 1000) * m2Price) + 2.5
    // total = jm_unit_price * qtyJm
    var perJmFixedFee = 2.5;
    var jmUnitPrice = (((haarA + haarB) / 1000) * m2Price) + perJmFixedFee;
    var total = jmUnitPrice * qtyJm;

    var price = total.toFixed(2);
    document.getElementById("spc_price").innerHTML = price.replace(".", ",") + " €";

    // Materjali nimi
    var matName = "";
    if(mat==="tsink") matName="Tsink";
    if(mat==="alutsink") matName="Alutsink";
    if(mat==="pol") matName="POL";
    if(mat==="pur") matName="PUR";
    if(mat==="pur_matt") matName="PUR MATT";

    // Parameetrid JSON (WPFormsile)
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
      jm_uhikuhind_eur_jm: Number(jmUnitPrice.toFixed(4)),
      hind_eur: Number(price)
    };
    var params = JSON.stringify(paramsObj);

    // WPForms: hind Hidden Field ID = 17
    var wpformsPriceId = 17;
    document.querySelectorAll('input[name="wpforms[fields][' + wpformsPriceId + ']"], textarea[name="wpforms[fields][' + wpformsPriceId + ']"]').forEach(function(el){
      el.value = price;
      el.dispatchEvent(new Event("input", {bubbles:true}));
      el.dispatchEvent(new Event("change", {bubbles:true}));
    });

    // WPForms: parameetrid Hidden field CSS class steel-orient-params
    document.querySelectorAll("input.steel-orient-params, textarea.steel-orient-params, .steel-orient-params input, .steel-orient-params textarea").forEach(function(el){
      el.value = params;
      el.dispatchEvent(new Event("input", {bubbles:true}));
      el.dispatchEvent(new Event("change", {bubbles:true}));
    });

    // Varuvariant: hind CSS class steel-orient-hind
    document.querySelectorAll("input.steel-orient-hind, textarea.steel-orient-hind, .steel-orient-hind input, .steel-orient-hind textarea").forEach(function(el){
      el.value = price;
      el.dispatchEvent(new Event("input", {bubbles:true}));
      el.dispatchEvent(new Event("change", {bubbles:true}));
    });

    // Täida automaatselt sõnum (kui tühi)
    var msgText =
      "Soovin pakkumist.\\n" +
      "Pikkus: " + l + " mm, Laius: " + w + " mm\\n" +
      "Kogus: " + qtyJm + " jm\\n" +
      "Materjal: " + matName + (ral ? (", RAL: " + ral) : "") + "\\n" +
      "Orienteeruv hind: " + price.replace(".", ",") + " €";
    steelSetMessageIfEmpty(msgText);
  };
})();
</script>
HTML;

  return $html;
});
