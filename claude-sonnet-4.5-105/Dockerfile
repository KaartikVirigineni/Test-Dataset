FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY ["DevPortal.csproj", "./"]
RUN dotnet restore "DevPortal.csproj"

COPY . .
RUN dotnet build "DevPortal.csproj" -c Release -o /app/build
RUN dotnet publish "DevPortal.csproj" -c Release -o /app/publish /p:UseAppHost=false

FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS runtime
WORKDIR /app
EXPOSE 8080
COPY --from=build /app/publish .
ENTRYPOINT ["dotnet", "DevPortal.dll"]