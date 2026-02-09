const listing = {};

const priceTable = {
	"gpt-5": {
		prompt_tokens: 1.25 / 1000000,
		completion_tokens: 10 / 1000000,
	},
	"gpt-5-mini": {
		prompt_tokens: 0.25 / 1000000,
		completion_tokens: 2.00 / 1000000,
	},
	"gpt-4.1-2025-04-14": {
		prompt_tokens: 2 / 1000000,
		completion_tokens: 8 / 1000000,
	},
	"gpt-4.5-preview-2025-02-27": {
		prompt_tokens: 75 / 1000000,
		completion_tokens: 150 / 1000000,
	},
	"gpt-4o-2024-08-06": {
		prompt_tokens: 2.5 / 1000000,
		completion_tokens: 10 / 1000000,
	},
	"o3-2025-04-16": {
		prompt_tokens: 10 / 1000000,
		completion_tokens: 40 / 1000000,
	},
	"o1-2024-12-17": {
		prompt_tokens: 15 / 1000000,
		completion_tokens: 60 / 1000000,
	},
	"o3-mini-2025-01-31": {
		prompt_tokens: 1.10 / 1000000,
		completion_tokens: 4.40 / 1000000,
	},
	"gemini-1.5-pro": {
		prompt_token_count: 0.075 / 1000000,
		candidates_token_count: 0.30 / 1000000,
	},
	"gemini-2.0-flash": {
		prompt_token_count: 0.10 / 1000000,
		candidates_token_count: 0.40 / 1000000,
	},
	"gemini-2.5-pro-exp-03-25": {
		prompt_token_count: 0.10 / 1000000,
		candidates_token_count: 0.40 / 1000000,
	},
	"anthropic.claude-3-5-sonnet-20240620-v1:0": {
		input_tokens: 0.003 / 1000,
		output_tokens: 0.015 / 1000,
	},
	"meta.llama3-70b-instruct-v1:0": {
		prompt_token_count: 0.00265 / 1000,
		generation_token_count: 0.0035 / 1000,
	},
	"deepseek-chat": {
		prompt_tokens: 0.27 / 1000000,
		completion_tokens: 1.10 / 1000000,
	},
	"deepseek-reasoner": {
		prompt_tokens: 0.55 / 1000000,
		completion_tokens: 2.19 / 1000000,
	},
	"grok-3": {
		prompt_tokens: 3 / 1000000,
		completion_tokens: 15 / 1000000,
	},
	"grok-4": {
		prompt_tokens: 3 / 1000000,
		completion_tokens: 15 / 1000000,
	},
	"dall-e-3": {
		image: 0.04,
	},
	"stability.stable-diffusion-xl-v1": {
		image: 0.04,
	},
	"amazon.titan-image-generator-v1": {
		image: 0.01,
	},
	"imagen-3.0-generate-002": {
		image: 0.03,
	},
};

function PopDebugger(pageNum) {
	const apiEndpoint = "/api/log/?page=";
	pageNum = pageNum || 1;
	fetch(
		apiEndpoint +
		pageNum +
		"c=" +
		Math.floor(Math.random() * 1000000)
	)
		.then((response) => response.json())
		.then((response) => {
			response.data.forEach(log => {
				if (!listing[log.workflowId]) {
					listing[log.workflowId] = [];
				}
				try {
					if (log.body) {
						log.body = JSON.parse(log.body);
					}
				} catch (e) {
					console.error("Error parsing body for", log.workflowId || "", e);
				}
				try {
					if (log.response) {
						log.response = JSON.parse(log.response);
					}
				} catch (e) {
					console.error("Error parsing response for", log.workflowId || "", e);
				}
				listing[log.workflowId].unshift(log);
			});
			console.log(listing);
			for (let workflowId in listing) {
				const workflow = listing[workflowId];
				const firstLog = workflow[0];
				const workflowEl = document.createElement("ul");
				workflowEl.className = "workflow";
				workflowEl.innerHTML = `
					<li><button>${firstLog.workflowId.substring(0,8)}</button> - ${firstLog.title}</li>
				`;
				document.getElementById("debugger-listing").appendChild(workflowEl);
				workflowEl.addEventListener("click", (e) => {
					e.preventDefault();
					PopToc(workflowId);
					PopApi(workflowId);
				});
			}
		});
}

function PopToc(workflowId) {
	const containerEl = document.getElementById("debugger-toc");
	containerEl.innerHTML = "<h3>Workflow: <span class='workflow-id'>" + workflowId.substring(0,8) + "</span></h3>";
	const tocEl = document.createElement("ul");
	tocEl.className = "toc";
	const workflow = listing[workflowId];
	workflow.forEach(log => {
		const logEl = document.createElement("li");
		logEl.className = "log";
		logEl.innerHTML = `
			<button>
				${log.action}
				<br/>
				<span class="model">${log.model.substring(0, 30)}</span>
			</button>
		`;
		tocEl.appendChild(logEl);
		logEl.addEventListener("click", (e) => {
			e.preventDefault();
			PopDetail(log);
		});
	});
	containerEl.appendChild(tocEl);
}

function PopApi(workflowId) {
	const containerEl = document.getElementById("debugger-api");
	containerEl.innerHTML = "<h3>API Details</h3>";

	const workflow = listing[workflowId];
	const tokenCount = {
	};
	for (let log of workflow) {
		let result = log.result;
		const model = log.model;
		console.log(model);
		console.log(priceTable[model]);
		if (result && result.tokens) {
			// Loop through the tokens and add them to the token count
			for (let token in result.tokens) {
				if (!tokenCount[token]) {
					tokenCount[token] = {
						count: 0,
						price: 0
					};
				}
				tokenCount[token].count += result.tokens[token];
				tokenCount[token].price += result.tokens[token] * priceTable[model][token];
			}
		}
	}

	let totalPrice = Object.values(tokenCount).reduce((acc, token) => acc + token.price, 0);

	const tokenEl = document.createElement("div");
	tokenEl.className = "token";
	let priceHtml = "";
	priceHtml = `<table class="token-table">
		<tr>
			<th>Type</th>
			<th>Count</th>
			<th>Price (USD)</th>
		</tr>`;
	for (let token in tokenCount) {
		priceHtml += `
		<tr>
			<td>${token.split("_")[0]}</td>
			<td>${tokenCount[token].count}</td>
			<td>${tokenCount[token].price.toFixed(4)}</td>
		</tr>`;
	}
	priceHtml += `</table>`;
	tokenEl.innerHTML = `
		<h4>Total Cost: <span class='price'>${totalPrice.toFixed(4)} USD</span></h4>
		${priceHtml}
	`;
	containerEl.appendChild(tokenEl);

	document.getElementById("debugger-detail").innerHTML = "";
}

function PopDetail(log) {
	console.log(log);
	const containerEl = document.getElementById("debugger-detail");
	containerEl.innerHTML = `
		<h3>Details: ${log.action} <span class='timestamp'>${log.timestamp}</span></h3>
		<hr/>
		<h5>Model: ${log.model}</h5>
	`;

	const detailEl = document.createElement("div");
	detailEl.className = "detail";
	let keyEl;

	if (log.result.json?.url) {
		keyEl = document.createElement("div");
		keyEl.className = "key";
		keyEl.innerHTML = `<h4>Preview</h4><img src="${log.result.json.url}" alt="Preview" width="200" />`;
		detailEl.appendChild(keyEl);
	}

	if (log.result.json) {
		keyEl = document.createElement("div");
		keyEl.className = "key";
		const keyTitle = document.createElement('h4');
		keyTitle.textContent = "Result JSON";
		keyEl.appendChild(keyTitle);
		keyEl.appendChild(buildTreeView(log.result.json));
		detailEl.appendChild(keyEl);
	}

	for (let key in log) {
		const keyEl = document.createElement("div");
		keyEl.className = "key";
		
		if (key === "body" || key === "response" || key === "result" || key === "payload") {
			const keyTitle = document.createElement('h4');
			keyTitle.textContent = key;
			if (typeof log[key] === "object") {
				keyEl.appendChild(keyTitle);
				keyEl.appendChild(buildTreeView(log[key]));
			} else {
				keyEl.innerHTML = `<h4>${key}</h4> ${log[key]}`;
			}
		}
		
		detailEl.appendChild(keyEl);
	}
	containerEl.appendChild(detailEl);
}

function buildTreeView(obj, depth = 0) {
	if (!obj) return '';
	
	const container = document.createElement('div');
	container.className = 'tree-container';
	
	for (let key in obj) {
		const value = obj[key];
		const item = document.createElement('div');
		item.className = 'tree-item';
		item.style.marginLeft = `${depth * 20}px`;
		
		const keySpan = document.createElement('span');
		keySpan.className = 'tree-key';
		keySpan.textContent = key;
		
		if (typeof value === 'object' && value !== null) {
			const toggle = document.createElement('button');
			toggle.className = 'tree-toggle';
			toggle.textContent = '▶';
			
			const content = document.createElement('div');
			content.className = 'tree-content collapsed';
			content.appendChild(buildTreeView(value, depth + 1));
			
			toggle.onclick = () => {
				toggle.textContent = toggle.textContent === '▶' ? '▼' : '▶';
				content.classList.toggle('collapsed');
			};
			
			item.appendChild(toggle);
			item.appendChild(keySpan);
			item.appendChild(content);
		} else {
			const valueSpan = document.createElement('span');
			valueSpan.className = 'tree-value';
			valueSpan.textContent = value;
			
			item.appendChild(keySpan);
			item.appendChild(document.createTextNode(': '));
			item.appendChild(valueSpan);
		}
		
		container.appendChild(item);
	}
	
	return container;
}

PopDebugger();
