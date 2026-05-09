const axios = require('axios');

const API_KEY = process.env.OPENROUTER_API_KEY;

async function run() {
  try {
    const response = await axios.post(
      'https://openrouter.ai/api/v1/chat/completions',
      {
        model: "arcee-ai/trinity-large-preview:free",
        messages: [
          { role: "system", content: "You are a helpful assistant." },
          { role: "user", content: "Buat ringkasan kode Laravel saya" }
        ],
      },
      {
        headers: {
          "Authorization": `Bearer ${API_KEY}`,
          "Content-Type": "application/json"
        }
      }
    );

    console.log(response.data.choices[0].message.content);
  } catch (err) {
    console.error("Error:", err.response ? err.response.data : err.message);
  }
}

run();
