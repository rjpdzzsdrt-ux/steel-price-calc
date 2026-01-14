<?php
/**
 * Plugin Name: Steel.ee Price Calculator
 * Description: Hinnakalkulaator (materjal + haar A/B + jm) + WPForms päringu täitmine (hind ID=17, parameetrid steel-orient-params)
 * Version: 2.1
 */

if (!defined('ABSPATH')) exit;

add_shortcode('steel_price_calc', function () {
  return '
  <div style="border:1px solid #ddd;padding:16px;border-radius:10px;max-width:520px">
    <h3 style="margin:0 0 12px 0">Arvuta orienteeruv hind (v2.1)</h3>

    <label style="display:block;margin-bottom:8px">Pikkus (mm) — haar A<br>
      <input id="spc_l" type="number" min="1" inputmode="numeric"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <label style="display:block;margin-bottom:8px">Laius (mm) — haar B<br>
      <input id="spc_w" type="number" min="1" inputmode="numeric"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <label style="display:block;margin-bottom:8px">Kogus (tk)<br>
      <input id="spc_q" type="number" min="1" value="1" inputmode="numeric"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <label style="display:block;margin-bottom:8px">Materjal<br>
      <select id="spc_mat" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
        <option value="tsink">Tsink (7 €/m²)</option>
        <option value="alutsink">Alutsink (7 €/m²)</option>
        <option value="pol">POL (7.5 €/m²)</option>
        <option value="pur" selected>PUR (8 €/m²)</option>
        <option value="pur_matt">PUR MATT (11 €/m²)</option>
      </select>
    </label>

    <label style="display:block;margin-bottom:8px">JM hind (€/jm)<br>
      <input id="spc_jm" type="number" min="0" step="0.01" value="3.00" inputmode="decimal"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
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
      <div id="spc_breakdown" style="font-size:12px;opacity:.8;margin-top:6px;line-height:1.4"></div>
      <div style="font-size:12px;opacity:.65;margin-top:6px">
        Orienteeruv hind. Täpne hind kinnitatakse pakkumisel.
      </div>
    </div>

    <script>
    function steelCalcPrice(){
      var l = Number(document.getElementById("spc_l").value || 0);
      var w = Number(document.getElementById("spc_w").value || 0);
      var q = Number(document.getElementById("spc_q").value || 1);
      var mat = document.getElementById("spc_mat").value;
      var jmPrice = Number(document.getElementById("spc_jm").value || 0);
      var ral = (document.getElementById("spc_ral").value || "").trim();

      if(l<=0 || w<=0 || q<=0){
        document.getElementById("spc_price").innerHTML = "Palun sisesta mõõdud ja kogus";
        document.getElementById("spc_breakdown").innerHTML = "";
        return;
      }
      if(jmPrice < 0){
        document.getElementById("spc_price").innerHTML = "JM hind ei saa olla negatiivne";
        document.getElementById("spc_breakdown").innerHTML = "";
        return;
      }

      // Haar A ja Haar B: kummalegi +10mm (toorik)
      var haarA = l + 10; // mm
      var haarB = w + 10; // mm

      // Materjali m2 hinnad (sinu hinnad)
      var baseM2 = 8; // PUR default
      if(mat === "tsink")     baseM2 = 7;
      if(mat === "alutsink") baseM2 = 7;
      if(mat === "pol")      baseM2 = 7.5;
      if(mat === "pur")      baseM2 = 8;
      if(mat === "pur_matt") baseM2 = 11;

      // Arvutused
      var m2 = (haarA/1000) * (haarB/1000); // tooriku pindala
      var jm = (haarA/1000);                // jm haar A järgi

      var materialCost = (m2 * baseM2) * q;
      var jmCost = (jm * jmPrice) * q;
      var total = materialCost + jmCost;

      var price = total.toFixed(2);

      document.getElementById("spc_price").innerHTML = price.replace(".", ",") + " €";

      // breakdown
      var matName = "";
      if(mat==="tsink") matName="Tsink";
      if(mat==="alutsink") matName="Alutsink";
      if(mat==="pol") matName="POL";
      if(mat==="pur") matName="PUR";
      if(mat==="pur_matt") matName="PUR MATT";

      var breakdown =
        "Haar A: " + haarA + " mm, Haar B: " + haarB + " mm<br>" +
        "Toorik: " + m2.toFixed(4) + " m² × " + baseM2 + " €/m² × " + q + " tk = " + materialCost.toFixed(2) + " €<br>" +
        "JM: " + jm.toFixed(3) + " jm × " + jmPrice.toFixed(2) + " €/jm × " + q + " tk = " + jmCost.toFixed(2) + " €<br>" +
        "Materjal: " + matName + (ral ? (", toon: " + ral) : "");

      document.getElementById("spc_breakdown").innerHTML = breakdown;

      // Parameetrid JSON (WPFormsile)
      var params = JSON.stringify({
        pikkus_mm: l,
        laius_mm: w,
        kogus_tk: q,
        haarA_mm: haarA,
        haarB_mm: haarB,
        materjal: matName,
        ral: ral,
        m2_toorik: Number(m2.toFixed(4)),
        jm: Number(jm.toFixed(3)),
        jm_hind_eur_jm: Number(jmPrice.toFixed(2)),
        m2_hind_eur: baseM2,
        materjalikulu_eur: Number(materialCost.toFixed(2)),
        jmkulu_eur: Number(jmCost.toFixed(2)),
        hind_eur: Number(price)
      });

      // ✅ 1) WPForms: hind Hidden Field ID = 17 (täida KÕIK sobivad väljad lehel)
      var wpformsPriceId = 17;
      document.querySelectorAll(
        \'input[name="wpforms[fields][\' + wpformsPriceId + \']"], textarea[name="wpforms[fields][\' + wpformsPriceId + \']"]\'
      ).forEach(function(el){
        el.value = price;
        el.dispatchEvent(new Event("input", {bubbles:true}));
        el.dispatchEvent(new Event("change", {bubbles:true}));
      });

      // ✅ 2) WPForms: parameetrid Hidden field CSS class steel-orient-params
      document.querySelectorAll(
        "input.steel-orient-params, textarea.steel-orient-params, .steel-orient-params input, .steel-orient-params textarea"
      ).forEach(function(el){
        el.value = params;
        el.dispatchEvent(new Event("input", {bubbles:true}));
        el.dispatchEvent(new Event("change", {bubbles:true}));
      });

      // ✅ Varuvariant: hind CSS class steel-orient-hind
      document.querySelectorAll(
        "input.steel-orient-hind, textarea.steel-orient-hind, .steel-orient-hind input, .steel-orient-hind textarea"
      ).forEach(function(el){
        el.value = price;
        el.dispatchEvent(new Event("input", {bubbles:true}));
        el.dispatchEvent(new Event("change", {bubbles:true}));
      });
    }
    </script>
  </div>';
});
