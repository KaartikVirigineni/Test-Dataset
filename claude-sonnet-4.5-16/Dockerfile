FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src
COPY ["WarehouseHub.csproj", "./"]
RUN dotnet restore "WarehouseHub.csproj"
COPY . .
RUN dotnet build "WarehouseHub.csproj" -c Release -o /app/build
RUN dotnet publish "WarehouseHub.csproj" -c Release -o /app/publish /p:UseAppHost=false

FROM mcr.microsoft.com/dotnet/aspnet:8.0
WORKDIR /app
COPY --from=build /app/publish .
EXPOSE 8080
ENV ASPNETCORE_URLS=http://+:8080
ENTRYPOINT ["dotnet", "WarehouseHub.dll"]