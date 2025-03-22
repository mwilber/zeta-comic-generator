const listing = {};

const priceTable = {
	"gpt-4.5-preview-2025-02-27": {
		completion: 150,
		prompt: 75,
		multiplier: 1000000,
	},
	"gpt-4o-2024-08-06": {
		completion: 10,
		prompt: 2.5,
		multiplier: 1000000,
	},
	"dall-e-3": {
		image: 0.04,
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
			<button>${log.action}</button>
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
		completion: {
			count: 0,
			price: 0
		},
		prompt: {
			count: 0,
			price: 0
		},
		total: {
			count: 0,
			price: 0
		},
		image: {
			count: 0,
			price: 0
		}
	};
	for (let log of workflow) {
		let response = log.response;
		const model = response.model || log.body.model;
		if (response && response.usage) {
			tokenCount.completion.count += response.usage.completion_tokens;
			tokenCount.completion.price += (response.usage.completion_tokens / priceTable[model].multiplier) * priceTable[model].completion;
			tokenCount.prompt.count += response.usage.prompt_tokens;
			tokenCount.prompt.price += (response.usage.prompt_tokens / priceTable[model].multiplier) * priceTable[model].prompt;
			tokenCount.total.count += response.usage.total_tokens;
		} else if (log.action === "image") {
			tokenCount.image.count += 1;
			tokenCount.image.price += priceTable[model] ? priceTable[model].image : 0;
		}
	}
	tokenCount.total.price += tokenCount.completion.price + tokenCount.prompt.price;

	const totalPrice = tokenCount.completion.price + tokenCount.prompt.price + tokenCount.image.price;

	const tokenEl = document.createElement("div");
	tokenEl.className = "token";
	tokenEl.innerHTML = `
		<h4>Total Cost: <span class='price'>${totalPrice.toFixed(4)} USD</span></h4>
		<h4>Token Usage</h4>
		<p>Completion: ${tokenCount.completion.count} tokens, ${tokenCount.completion.price.toFixed(4)} USD</p>
		<p>Prompt: ${tokenCount.prompt.count} tokens, ${tokenCount.prompt.price.toFixed(4)} USD</p>
		<p>Total: ${tokenCount.total.count} tokens, ${tokenCount.total.price.toFixed(4)} USD</p>
		<h4>Image Usage</h4>
		<p>Image: ${tokenCount.image.count} images, ${tokenCount.image.price.toFixed(4)} USD</p>
	`;
	containerEl.appendChild(tokenEl);
}

function PopDetail(log) {
	console.log(log);
	const containerEl = document.getElementById("debugger-detail");
	containerEl.innerHTML = "<h3>Details: " + log.action + "<span class='timestamp'>" + log.timestamp + "</span></h3>";
	const detailEl = document.createElement("div");
	detailEl.className = "detail";

	let keyEl;

	if (log.result.json.url) {
		keyEl = document.createElement("div");
		keyEl.className = "key";
		keyEl.innerHTML = `<h4>Preview</h4><img src="${log.result.json.url}" alt="Preview" width="200" />`;
		detailEl.appendChild(keyEl);
	}

	keyEl = document.createElement("div");
		keyEl.className = "key";
	const keyTitle = document.createElement('h4');
		keyTitle.textContent = "Result JSON";
		keyEl.appendChild(keyTitle);
		keyEl.appendChild(buildTreeView(log.result.json));
		detailEl.appendChild(keyEl);

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