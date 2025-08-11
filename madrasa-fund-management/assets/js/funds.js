(function(){
	const { createElement: h, useState, useEffect } = wp.element;
	function App(){
		const [funds, setFunds] = useState([]);
		const [name, setName] = useState('');
		const [opening, setOpening] = useState('0');
		const [loading, setLoading] = useState(false);

		function fetchFunds(){
			setLoading(true);
			jQuery.post(mfmFunds.ajaxUrl, { action: 'mfm_list_funds', nonce: mfmFunds.nonce }, function(res){
				if(res && res.success){ setFunds(res.data.funds || []); }
				setLoading(false);
			});
		}
		useEffect(fetchFunds, []);

		function createFund(e){ e.preventDefault(); if(!name) return; setLoading(true);
			jQuery.post(mfmFunds.ajaxUrl, { action:'mfm_create_fund', nonce:mfmFunds.nonce, name, opening }, function(){ setName(''); setOpening('0'); fetchFunds(); });
		}
		function updateFund(f){
			const newName = prompt('Fund name', f.name); if(newName===null) return;
			const newOpening = prompt('Opening balance', f.opening_balance); if(newOpening===null) return;
			setLoading(true);
			jQuery.post(mfmFunds.ajaxUrl, { action:'mfm_update_fund', nonce:mfmFunds.nonce, id:f.id, name:newName, opening:newOpening }, function(){ fetchFunds(); });
		}
		function deleteFund(f){ if(!confirm('Delete fund and all its transactions?')) return; setLoading(true);
			jQuery.post(mfmFunds.ajaxUrl, { action:'mfm_delete_fund', nonce:mfmFunds.nonce, id:f.id }, function(){ fetchFunds(); });
		}

		return h('div', {},
			h('form', { onSubmit:createFund, className:'mfm-card', style:{marginBottom:'12px'} },
				h('h3', {}, 'Create Fund'),
				h('div', { className:'mfm-grid' },
					h('label', {}, 'Name', h('input', { value:name, onChange:e=>setName(e.target.value), required:true })),
					h('label', {}, 'Opening Balance', h('input', { value:opening, onChange:e=>setOpening(e.target.value), type:'number', step:'0.01' }))
				),
				h('button', { className:'button button-primary', type:'submit' }, 'Add Fund')
			),
			h('div', { className:'mfm-card' },
				h('h3', {}, 'All Funds'),
				loading ? h('p', {}, 'Loading...') : h('table', { className:'widefat fixed striped' },
					h('thead', {}, h('tr', {}, h('th', {}, 'Name'), h('th', {}, 'Opening Balance'), h('th', {}, 'Actions'))),
					h('tbody', {}, funds.map(f => h('tr', { key:f.id },
						h('td', {}, f.name),
						h('td', {}, Number(f.opening_balance).toFixed(2)),
						h('td', {},
							h('button', { className:'button', onClick:()=>updateFund(f) }, 'Edit'), ' ',
							h('button', { className:'button button-link-delete', onClick:()=>deleteFund(f) }, 'Delete')
						)
					)))
			)
		);
	}
	document.addEventListener('DOMContentLoaded', function(){
		const root = document.getElementById('mfm-funds-app'); if(!root) return;
		wp.element.render(h(App), root);
	});
})();