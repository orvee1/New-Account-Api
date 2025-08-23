<script>
(() => {
  // ===== Helpers =====
  function changeLabels(container, name) {
    if (!container) return;
    const star = '<span class="text-rose-600">*</span>';
    const num = container.querySelector('.number-label');
    const mark = container.querySelector('.mark-label');
    const ng   = container.querySelector('.ng-mark-label');
    const ngr  = container.querySelector('.ng-mark-range-label');

    if (num)  num.innerHTML  = `Number of ${name} ${star}`;
    if (mark) mark.innerHTML = `Mark of ${name} ${star}`;
    if (ng)   ng.innerHTML   = `${name} Negative Mark/stamp ${star}`;
    if (ngr)  ngr.innerHTML  = `${name} Negative Mark Range`;
  }

  // enable=false হলে inputs: disabled + name remove (submit হবে না)
  function setEnabled(container, enable) {
    if (!container) return;
    container.querySelectorAll('input, select, textarea').forEach(el => {
      if (enable) {
        if (el.dataset.name && !el.name) el.name = el.dataset.name;
        el.disabled = false;
        if (el.dataset.wasRequired) { el.required = true; delete el.dataset.wasRequired; }
      } else {
        if (el.name) { el.dataset.name = el.name; el.removeAttribute('name'); }
        if (el.required) el.dataset.wasRequired = '1';
        el.required = false;
        el.disabled = true;
      }
    });
  }

  function toggleBlock(el, show) {
    if (!el) return;
    el.classList.toggle('hidden', !show);
    setEnabled(el, show);
  }

  // Comma-separated numbers or ranges: -0.25, 0.5, 1-2, -1.5 - 0
  function validateRanges(val) {
    if (!val) return true; // empty allowed
    return val.split(',').every(seg => {
      seg = seg.trim();
      if (!seg) return false;
      return /^-?\d+(\.\d+)?(\s*-\s*-?\d+(\.\d+)?)?$/.test(seg);
    });
  }

  // Bengali→English digits + strip spaces/commas
  function bn2en(s) {
    if (s == null) return s;
    const map = {'০':'0','১':'1','২':'2','৩':'3','৪':'4','৫':'5','৬':'6','৭':'7','৮':'8','৯':'9'};
    return String(s).replace(/[০-৯]/g, d => map[d]).replace(/[\s,]+/g, '');
  }

  // ===== Cached refs =====
  const batchType = @json(old('batch_type', $type_info->batch_type ?? ''));
  const mcqData   = document.getElementById('mcq_data');
  const mcq2Data  = document.getElementById('mcq2_data');
  const batchSel  = document.getElementById('batch-type');
  const form      = document.getElementById('qt-form') || document.querySelector('form[action*="question-types"]');

  function applyBatch(type) {
    if (type === 'combined') {
      toggleBlock(mcq2Data, true);
      changeLabels(mcqData,  'MCQ-R');
      changeLabels(mcq2Data, 'MCQ-F');
    } else {
      toggleBlock(mcq2Data, false);
      changeLabels(mcqData,  'MCQ');
      changeLabels(mcq2Data, 'MCQ');
    }
  }

  // ===== Init =====
  document.addEventListener('DOMContentLoaded', () => {
    // Stash initial names once (for proper restore later)
    [mcq2Data].forEach(scope => {
      if (!scope) return;
      scope.querySelectorAll('input,select,textarea').forEach(el => {
        if (el.name) el.dataset.name = el.name;
      });
    });

    // initial state from old()/server
    applyBatch(batchType);

    // on change
    if (batchSel) {
      batchSel.addEventListener('change', e => applyBatch(e.target.value));
    }

    // live validation for negative mark ranges
    document.querySelectorAll('.nagetive_mark_range input').forEach(input => {
      input.addEventListener('input', function () {
        const ok = validateRanges(this.value.trim());
        this.classList.toggle('border-rose-500', !ok);
        this.classList.toggle('focus:border-rose-500', !ok);
      });
    });

    // sanitize numeric fields before submit
    if (form) {
      form.addEventListener('submit', e => {
        const fields = [
          'mcq_number','mcq_mark','mcq_negative_mark',
          'mcq2_number','mcq2_mark','mcq2_negative_mark',
          'sba_number','sba_mark','sba_negative_mark',
          'emq_number','emq_mark','emq_negative_mark',
          'pass_mark','duration','full_mark'
        ];
        fields.forEach(name => {
          const el = form.querySelector(`[name="${name}"]`);
          if (el && typeof el.value === 'string') el.value = bn2en(el.value.trim());
        });
      });
    }
  });
})();
</script>
