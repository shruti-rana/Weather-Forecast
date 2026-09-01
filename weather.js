const apiKey = "c2909248f7084aa0acd74102250210"; 
const searchInput = document.getElementById("searchInput");
const suggestionsEl = document.getElementById("suggestions");
const searchBtn = document.getElementById("searchBtn");
searchInput.addEventListener("input", () => {
 const query = searchInput.value.trim();
 if (query.length > 0) {
  fetch(
   `https://api.weatherapi.com/v1/search.json?key=${apiKey}&q=${encodeURIComponent(
    query
   )}`
  )
   .then((res) => res.json())
   .then((data) => {
    suggestionsEl.innerHTML = "";
    data.forEach((location) => {
     const div = document.createElement("div");
     div.textContent = `${location.name}, ${location.region}, ${location.country}`;
     div.addEventListener("click", () => {
      searchInput.value = location.name;
      suggestionsEl.innerHTML = "";
      getWeather(location.name);
     });
     suggestionsEl.appendChild(div);
    });
   })
   .catch(() => {
    suggestionsEl.innerHTML = "";
   });
 } else {
  suggestionsEl.innerHTML = "";
 }
});
searchBtn.addEventListener("click", () => {
 const city = searchInput.value.trim();
 if (city) {
  getWeather(city);
  suggestionsEl.innerHTML = "";
 }
});
window.onload = function () {
 getWeather("Los Angeles");
};
function getWeather(city) {
 // Current weather data
 fetch(
  `https://api.weatherapi.com/v1/current.json?key=${apiKey}&q=${encodeURIComponent(
   city
  )}`
 )
  .then((response) => response.json())
  .then((data) => {
   document.getElementById("cityName").textContent = data.location.name;
   document.getElementById("temperature").textContent = `${Math.round(
    data.current.temp_c
   )}°C`;
   document.getElementById("description").textContent =
    data.current.condition.text;
   document.getElementById("humidity").textContent = `Humidity: ${data.current.humidity}%`;
   document.getElementById("wind").textContent = `Wind speed: ${data.current.wind_kph} km/h`;
   document.getElementById("weatherIcon").textContent = getWeatherEmoji(
    data.current.condition.text
   );
   document.getElementById("datetime").textContent = data.location.localtime;
  })
  .catch(() => {
   alert("City not found or error fetching data");
  });
 // 1 day forecast
 fetch(
  `https://api.weatherapi.com/v1/forecast.json?key=${apiKey}&q=${encodeURIComponent(
   city
  )}&days=1`
 )
  .then((response) => response.json())
  .then((data) => {
   const forecastEl = document.getElementById("forecast");
   forecastEl.innerHTML = "";
   const day = data.forecast.forecastday[0];
   forecastEl.innerHTML = `
        <div class="forecast-day">
          ${new Date(day.date).toLocaleDateString([], {
           weekday: "short",
           month: "short",
           day: "numeric",
          })}:
          ${getWeatherEmoji(day.day.condition.text)}
          ${Math.round(day.day.avgtemp_c)}°C
          (${day.day.condition.text})
        </div>
      `;
  });
}
function getWeatherEmoji(description) {
 if (description.includes("Clear")) return "☀️";
 if (description.includes("Cloud")) return "🌥️";
 if (description.includes("Rain")) return "🌧️";
 if (description.includes("Snow")) return "🌨️";
 if (description.includes("Thunder")) return "⛈️";
 return "🌡️";
} 